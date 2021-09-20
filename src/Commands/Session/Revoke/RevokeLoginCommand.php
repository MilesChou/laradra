<?php

namespace Laradra\Commands\Session\Revoke;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laradra\Exceptions\Command\TimesUpException;
use Laradra\SessionManager;

class RevokeLoginCommand extends Command
{
    protected $signature = 'hydra:session:revoke:login
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
            Log::info('Login session revoke success');
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

        $query = (new SessionManager())->newRevokeLoginSessionQuery($start, $end, $this->option('subject'));

        if ($limit = (int)$this->option('limit')) {
            $query->limit($limit);
        }

        if ($this->option('dry-run')) {
            $this->info('Count: ' . $query->count());

            return;
        }

        $usleep = 1000 * $this->option('sleep');

        $authenticationCompleted = false;

        while (!$authenticationCompleted) {
            $startTime = microtime(true);

            $authenticationRows = $query->delete();

            $this->info(sprintf(
                "[%s] Delete authentication session rows: %3d ,  time use %9.3f ms",
                Carbon::now()->toDateTimeString(),
                $authenticationRows,
                (microtime(true) - $startTime) * 1000
            ));

            $authenticationCompleted = 0 === $authenticationRows;

            if ($ttl !== -1 && microtime(true) - LARAVEL_START > $ttl) {
                throw new TimesUpException();
            }

            usleep($usleep);
        }

        $this->output->success(sprintf('Datetime range:  %s - %s', $start, $end));
    }
}
