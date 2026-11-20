<?php
declare(strict_types=1);

namespace BasecoatTheme\Model\Filter;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * `LOWER(field) LIKE '%term%' ESCAPE '|'`: case-insensitive "contains" where `%` and `_` typed by
 * the user are plain characters. Portable (PostgreSQL, MySQL, SQLite: they share no default escape).
 */
final class ContainsExpression implements ExpressionInterface
{
    private IdentifierExpression $field;

    /**
     * @param string $field Qualified field (`Articles.title`).
     * @param string $term Text to find.
     */
    public function __construct(string $field, private string $term)
    {
        $this->field = new IdentifierExpression($field);
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $placeholder = $binder->placeholder('c');
        $escaped = str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower($this->term));
        $binder->bind($placeholder, '%' . $escaped . '%', 'string');

        return 'LOWER(' . $this->field->sql($binder) . ') LIKE ' . $placeholder . " ESCAPE '|'";
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        // Exposed so the identifier quoter can quote it when `quoteIdentifiers` is on
        $callback($this->field);

        return $this;
    }
}
