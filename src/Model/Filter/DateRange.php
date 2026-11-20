<?php
declare(strict_types=1);

namespace BasecoatTheme\Model\Filter;

use Cake\I18n\Date;
use Cake\ORM\Table;
use DateTimeImmutable;
use Search\Manager;
use Search\Model\Filter\Base;

/**
 * friendsofcake/search filter: date range, both days included (`?created[from]=2026-01-01&created[to]=2026-01-31`).
 *
 * ```
 * ->add('created', 'BasecoatTheme.DateRange', ['label' => __('Created')])
 * ```
 *
 * Either end may be left out. Values that are not real `Y-m-d` dates are ignored. On a datetime
 * column the `to` day is included up to its last second.
 */
class DateRange extends Base
{
    /**
     * @param string $name Name.
     * @param \Search\Manager $manager Manager.
     * @param array<string, mixed> $config Config.
     */
    public function __construct(string $name, Manager $manager, array $config = [])
    {
        // `created[from]`/`created[to]` arrive as one array (the processor flattens arrays otherwise)
        parent::__construct($name, $manager, $config + ['flatten' => false]);
    }

    /**
     * @inheritDoc
     */
    public function process(): bool
    {
        $value = $this->getArgs()[$this->name()] ?? null;
        if (!is_array($value)) {
            return false;
        }
        $from = $this->date($value['from'] ?? null);
        $to = $this->date($value['to'] ?? null);
        if ($from === null && $to === null) {
            return false;
        }

        $repository = $this->manager()->getRepository();
        $fields = (array)$this->getConfig('fields');
        foreach ($this->fields() as $i => $field) {
            $isDate = $repository instanceof Table
                && $repository->getSchema()->getColumnType((string)$fields[$i]) === 'date';
            if ($from !== null) {
                $this->getQuery()->andWhere([$field . ' >=' => $isDate ? new Date($from) : $from . ' 00:00:00']);
            }
            if ($to !== null) {
                $this->getQuery()->andWhere($isDate
                    ? [$field . ' <=' => new Date($to)]
                    : [$field . ' <' => (new Date($to))->addDays(1)->format('Y-m-d') . ' 00:00:00']);
            }
        }

        return true;
    }

    /**
     * @param mixed $value Value.
     * @return string|null `Y-m-d` if it is a real date.
     */
    private function date(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
