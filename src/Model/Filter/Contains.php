<?php
declare(strict_types=1);

namespace BasecoatTheme\Model\Filter;

use Cake\Database\Expression\QueryExpression;
use Search\Model\Filter\Base;

/**
 * friendsofcake/search filter: free-text search over several fields.
 *
 * ```
 * ->add('q', 'BasecoatTheme.Contains', ['fields' => ['title', 'body']])
 * ```
 *
 * Every word must appear in some field (`world news` finds "news about the world"), ignoring
 * case on any database (`Search.Like` needs `ILIKE` on PostgreSQL, which the others lack);
 * `%` and `_` typed by the user are plain characters. At most 5 words of up to 100 characters.
 */
class Contains extends Base
{
    private const MAX_TERMS = 5;
    private const MAX_LENGTH = 100;

    /**
     * @inheritDoc
     */
    public function process(): bool
    {
        $value = $this->value();
        if (!is_string($value)) {
            return false;
        }
        $terms = preg_split('/\s+/u', trim(mb_substr($value, 0, self::MAX_LENGTH)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($terms === []) {
            return false;
        }

        $fields = $this->fields();
        foreach (array_slice(array_values(array_unique($terms)), 0, self::MAX_TERMS) as $term) {
            $this->getQuery()->where(fn(QueryExpression $exp) => $exp->or(array_map(
                fn(string $field) => new ContainsExpression($field, $term),
                $fields,
            )));
        }

        return true;
    }
}
