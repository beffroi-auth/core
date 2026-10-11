<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Listing\Period;

/**
 * What a reading asks of the journal of one person (issue #591): which acts, of which application, over
 * which stretch of time. How they are read is the Pagination beside it, and whose journal it is is the
 * person the port is handed.
 *
 * **Each of the three is open, and open means every one.** A question that names no act asks for every act
 * of that person, which is what a register attached to a request under article 15 of the GDPR is; one that
 * names no client asks for every application; one that names no period reads the whole of what the journal
 * still holds. A reading that answered nothing to a question that asked for nothing would be a page
 * somebody reloads.
 *
 * The three travel together because they are one question: the page of an account asks two acts over a
 * period, the console asks whatever the request it answers names, and a filter this journal comes to serve
 * is a field here rather than a parameter added to every store that answers.
 */
final readonly class JournalQuestion
{
    /**
     * @param list<string> $acts the names the events give themselves, empty for every act
     * @param string|null $client the application an act was done on behalf of, by the identifier it was registered under; null for every application
     * @param Period|null $period the stretch of time to read, null for the whole of the journal
     */
    public function __construct(
        public array $acts = [],
        public ?string $client = null,
        public ?Period $period = null,
    ) {
    }
}
