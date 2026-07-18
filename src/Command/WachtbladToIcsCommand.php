<?php

declare(strict_types=1);

namespace App\Command;

use App\Ics\IcsCalendarBuilder;
use App\Ics\IcsEvent;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[AsCommand(
    name: 'wachtblad:sync-ics',
    description: 'Haalt wachtdienst-shiften op van het CPZ-intranet en schrijft ze weg als ICS-bestand',
)]
final class WachtbladToIcsCommand extends Command
{
    private const BASE_URL = 'https://wachtblad.intranetcpz.be/ajax/kalender/events/wacht/telefoon';
    private const KALENDER_ID = '1';

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Startdatum (YYYY-MM-DD)')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'Einddatum (YYYY-MM-DD)')
            ->addOption('out', null, InputOption::VALUE_REQUIRED, 'Pad naar output .ics-bestand', 'wachtblad.ics')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Neem alle events op, niet enkel de eigen shiften');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cookie = $_ENV['SESSION_COOKIE'];
        
        if ($cookie === false || $cookie === '') {
            $io->error(
                'Geen WACHTBLAD_COOKIE omgevingsvariabele gevonden. '
                . 'Log in via de browser, kopieer de Cookie-header uit DevTools '
            );

            return Command::FAILURE;
        }

        $start = $input->getOption('start');
        $end = $input->getOption('end');

        if ($start === null || $end === null) {
            $io->error('--start en --end zijn verplicht (formaat YYYY-MM-DD).');

            return Command::FAILURE;
        }

        try {
            $rawEvents = $this->fetchEvents(
                $start,
                $end,
                $cookie
            );
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $events = array_map(
            IcsEvent::fromApiResponse(...),
            $rawEvents,
        );

        $onlyOwn = !$input->getOption('all');
        $ownCount = \count(array_filter(
            $events,
            static fn(IcsEvent $e): bool => $e->isOwn
        ));

        $builder = new IcsCalendarBuilder();
        $ics = $builder->build($events, $onlyOwn);

        $outPath = $input->getOption('out');
        file_put_contents($outPath, $ics);

        $io->success(sprintf(
            '%d event(s) weggeschreven naar %s (%d eigen shift(s) gevonden).',
            $onlyOwn ? $ownCount : count($events),
            $outPath,
            $ownCount,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEvents(
        string
        $start,
        string $end,
        string $cookie
    ): array {
        $client = HttpClient::create();

        try {
            $response = $client->request(
                'GET',
                self::BASE_URL,
                [
                    'query' => [
                        'start' => $start,
                        'end' => $end,
                        'kalender_id' => self::KALENDER_ID,
                        'overzicht' => 'YES',
                    ],
                    'headers' => [
                        'Cookie' => $cookie,
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 20,
                ]
            );

            $statusCode = $response->getStatusCode();

            if (
                in_array(
                    $statusCode,
                    [401, 403],
                    true
                )
            ) {
                throw new RuntimeException(
                    "HTTP {$statusCode}: sessie-cookie is waarschijnlijk verlopen of ongeldig."
                );
            }

            if ($statusCode !== 200) {
                throw new RuntimeException("Onverwachte statuscode: {$statusCode}");
            }

            /** @var array<int, array<string, mixed>> $data */
            $data = $response->toArray();
            return $data;
        } catch (ExceptionInterface $e) {
            throw new RuntimeException('Kon de wachtblad-server niet bereiken: ' . $e->getMessage());
        }
    }
}
