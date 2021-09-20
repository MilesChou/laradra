<?php

namespace Laradra\Commands\Session\Revoke;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Laradra\Models\HydraOauth2AuthenticationSession;
use Laradra\Models\HydraOauth2ConsentRequest;

class ExplainCommand extends Command
{
    protected $signature = 'hydra:session:revoke:explain
                            {--subject=}
                            {--ago=86400}
                            {--timebox=3600}
                            {--limit=1}
                            ';

    protected $description = 'Explain the revoke SQL';

    public function handle(): int
    {
        $ago = (int)$this->option('ago');
        $timebox = (int)$this->option('timebox');

        $offset = Carbon::now()
            ->utc()
            ->subSeconds($ago);

        $end = $offset->toDateTimeString();
        $start = $offset->subSeconds($timebox)->toDateTimeString();

        $this->comment(sprintf('Datetime range:  %s - %s', $start, $end));

        $authenticationQuery = $this->buildAuthenticationQuery($start, $end);

        $this->info('Authentication session SQL: ' . $authenticationQuery->toSql());
        $this->info('Explain:');

        $explain = $authenticationQuery->explain()
            ->map(function ($value) {
                return (array)$value;
            })
            ->toArray();

        $this->table(array_keys($explain[0]), $explain);

        $consentQuery = $this->buildConsentQuery($start, $end);

        $this->info('Consent request SQL: ' . $consentQuery->toSql());
        $this->info('Explain:');

        $explain = $consentQuery->explain()
            ->map(function ($value) {
                return (array)$value;
            })
            ->toArray();

        $this->table(array_keys($explain[0]), $explain);

        return 0;
    }

    private function buildAuthenticationQuery(string $start, string $end): Builder
    {
        $subject = $this->option('subject');

        /** @see HydraOauth2AuthenticationSession::scopeBetweenWithSubject() */
        /** @see HydraOauth2AuthenticationSession::scopeBetween() */
        /** @var Builder $query */
        $query = $subject
            ? HydraOauth2AuthenticationSession::betweenWithSubject($start, $end, $subject)
            : HydraOauth2AuthenticationSession::between($start, $end);

        if ($limit = (int)$this->option('limit')) {
            $query->limit($limit);
        }

        return $query;
    }

    private function buildConsentQuery(string $start, string $end): Builder
    {
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

        return $query;
    }
}
