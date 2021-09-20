<?php

namespace Laradra\Commands\Session\Revoke;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Laradra\Models\HydraOauth2ConsentRequest;

class ExplainConsentCommand extends Command
{
    protected $signature = 'hydra:session:revoke:explain:consent
                            {--subject=}
                            {--ago=86400}
                            {--timebox=3600}
                            {--limit=1}
                            ';

    protected $description = 'Explain the SQL revoking consent session';

    public function handle(): int
    {
        $ago = (int)$this->option('ago');
        $timebox = (int)$this->option('timebox');

        $offset = Carbon::now()
            ->utc()
            ->subSeconds($ago);

        $end = $offset->toDateTimeString();
        $start = $offset->subSeconds($timebox)->toDateTimeString();

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

        $this->info('Consent request SQL: ' . $query->toSql());

        $explain = $query->explain()
            ->map(function ($value) {
                return (array)$value;
            })
            ->toArray();

        $this->table(array_keys($explain[0]), $explain);

        return 0;
    }
}
