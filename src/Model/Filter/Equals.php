<?php
declare(strict_types=1);

namespace BasecoatTheme\Model\Filter;

use Cake\ORM\Table;
use Cake\Validation\Validation;
use Search\Model\Filter\Base;

/**
 * friendsofcake/search filter: `field = value`, only when the value fits the column type, so a
 * hand-edited URL (`?category_id=abc` on an integer or uuid column) is ignored instead of making
 * the database fail.
 *
 * ```
 * ->add('category_id', 'BasecoatTheme.Equals', ['label' => __('Category')])
 * ->add('status', 'BasecoatTheme.Equals', ['options' => ['draft' => __('Draft'), 'live' => __('Live')]])
 * ```
 *
 * With `options`, only those keys are accepted (and the filter bar shows them); without them, a
 * belongsTo foreign key shows the related records.
 */
class Equals extends Base
{
    /**
     * @inheritDoc
     */
    public function process(): bool
    {
        $value = $this->value();
        if (!is_string($value) && !is_int($value)) {
            return false;
        }
        $value = (string)$value;
        $options = $this->getConfig('options');
        if (is_array($options) && !array_key_exists($value, $options)) {
            return false;
        }
        $repository = $this->manager()->getRepository();
        if ($repository instanceof Table) {
            foreach ((array)$this->getConfig('fields') as $field) {
                if (!$this->fitsColumn($repository, (string)$field, $value)) {
                    return false;
                }
            }
        }

        $conditions = array_map(fn(string $field) => [$field => $value], $this->fields());
        $this->getQuery()->andWhere(['OR' => $conditions]);

        return true;
    }

    /**
     * @param \Cake\ORM\Table $table Table.
     * @param string $field Column.
     * @param string $value Value.
     * @return bool Whether the database can compare the value with the column.
     */
    private function fitsColumn(Table $table, string $field, string $value): bool
    {
        return match ($table->getSchema()->getColumnType($field)) {
            'integer', 'biginteger', 'smallinteger', 'tinyinteger' => (bool)preg_match('/^-?\d{1,18}$/', $value),
            'decimal', 'float' => is_numeric($value),
            'uuid', 'binaryuuid' => Validation::uuid($value),
            'boolean' => in_array($value, ['0', '1'], true),
            'date', 'datetime', 'datetimefractional', 'timestamp', 'timestampfractional', 'timestamptimezone' => false,
            default => mb_strlen($value) <= 255,
        };
    }
}
