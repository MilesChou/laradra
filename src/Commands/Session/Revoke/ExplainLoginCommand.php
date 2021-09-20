<?php

namespace Laradra\Commands\Session\Revoke;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Laradra\SessionManager;

class ExplainLoginCommand extends Command
{
    protected $signature = 'hydra:session:revoke:explain:login
                            {--subject=}
                            {--ago=86400}
                            {--timebox=3600}
                            {--limit=1}
                            ';

    protected $description = 'Explain the SQL revoking login session';

    public function handle(): int
    {
        $offset = Carbon::now()
            ->utc()
            ->subSeconds((int)$this->option('ago'));

        $start = $offset->subSeconds((int)$this->option('timebox'))->toDateTimeString();
        $end = $offset->toDateTimeString();

        $query = (new SessionManager())->newRevokeLoginSessionQuery($start, $end, $this->option('subject'));

        if ($limit = (int)$this->option('limit')) {
            $query->limit($limit);
        }

        $this->info('SQL: ' . $query->toSql());

        $explain = $query->explain()
            ->map(function ($value) {
                return (array)$value;
            })
            ->toArray();

        $this->table(array_keys($explain[0]), $explain);

        return 0;
    }
}
