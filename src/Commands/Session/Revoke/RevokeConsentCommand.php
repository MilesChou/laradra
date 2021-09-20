<?php

namespace Laradra\Commands\Session\Revoke;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Laradra\Exceptions\Command\TimesUpException;
use Laradra\Models\HydraOauth2ConsentRequest;

class RevokeConsentCommand extends Command
{
    protected $signature = 'hydra:session:revoke:consent
                            {--subject=}
                            {--ago=86400}
                            {--timebox=3600}
                            {--limit=1}
                            {--sleep=10}
                            {--step=42}
                            {--ttl=10 : ms, -1 is no limit}
                            {--dry-run}
                            ';

    protected $description = '根據條件清除 Hydra 的 session';

    public function handle(): int
    {
        $ago = (int)$this->option('ago');
        $timebox = (int)$this->option('timebox');
        $step = (int)$this->option('step');
        $ttl = (int)$this->option('ttl');

        $offset = Carbon::now()
            ->utc()
            ->subSeconds($ago);

        try {
            if ($step > 0) {
                for ($i = 0; $i < $step; $i++) {
                    $this->deleteTimeBox($offset->subSeconds($timebox), $ttl);
                }
            }
        } catch (TimesUpException $e) {
            $this->comment("Time's up, cancel job.");
        }

        if (!$this->option('dry-run')) {
            Log::info('Consent session revoke success');
        }

        return 0;
    }

    /**
     * @param Carbon $offset
     * @param int $ttl
     * @throws TimesUpException
     */
    private function deleteTimeBox(Carbon $offset, int $ttl): void
    {
        $start = $offset->toDateTimeString();
        $end = $offset->copy()->addSeconds($this->option('timebox'))->toDateTimeString();

        if ($this->output->isVerbose()) {
            $this->comment(sprintf(
                '[%s] Datetime range:  %s - %s',
                Carbon::now()->toDateTimeString(),
                $start,
                $end
            ));
        }

        $subject = $this->option('subject');

        /** @see HydraOauth2ConsentRequest::scopeBetweenWithSubject() */
        /** @see HydraOauth2ConsentRequest::scopeBetween() */
        /** @var Builder $query */
        $query = $subject
            ? HydraOauth2ConsentRequest::betweenWithSubject($start, $end, $subject)
            : HydraOauth2ConsentRequest::between($start, $end);

        if ($limit = (int)$this->option('limit')) {
            $query->limit($limit);
        }


        if ($this->option('dry-run')) {
            $this->info('Consent request Count: ' . $query->count());

            return;
        }

        $usleep = 1000 * $this->option('sleep');

        $consentCompleted = false;

        while (!$consentCompleted) {
            $startTime = microtime(true);

            $consentRows = $query->delete();

            $this->info(sprintf(
                "[%s] Delete consent request rows:        %3d ,  time use %9.3f ms",
                Carbon::now()->toDateTimeString(),
                $consentRows,
                (microtime(true) - $startTime) * 1000
            ));

            $consentCompleted = 0 === $consentRows;

            if ($ttl !== -1 && microtime(true) - LARAVEL_START > $ttl) {
                throw new TimesUpException();
            }

            usleep($usleep);
        }

        $this->output->success(sprintf('Datetime range:  %s - %s', $start, $end));
    }
}
