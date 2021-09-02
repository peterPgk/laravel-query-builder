namespace Spatie\QueryBuilder\Filters;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;


/**
 * Search in several fields with one value
 *
 * Class FieldsFilterPartial
 * @package App\LaravelDataTables
 */
class FieldsFilterPartial extends FiltersPartial implements Filter
{

    /**
     * @var array
     */
    protected $fields;


    public function __construct(array $fields = [], bool $addRelationConstraint = true)
    {
        $this->fields = $fields;

        parent::__construct($addRelationConstraint);
    }

    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where(function($query) use ($value) {
            foreach ($this->fields as $property) {
                if ($this->isRelationProperty($query, $property)) {
                    if ($this->addRelationConstraint) {
                        $this->withRelationConstraint($query, $value, $property);
                    }
                    continue;
                }

                $query->orWhere($property, 'LIKE', '%' . $value . '%');
            }
        });

        return $query;
    }

    /**
     * @param Builder $query
     * @param $value
     * @param string $property
     */
    protected function withRelationConstraint(Builder $query, $value, string $property)
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except(count($parts) - 1)->map([Str::class, 'camel'])->implode('.'),
                    $parts->last(),
                ];
            });

        $query->orWhereHas($relation, function (Builder $query) use ($value, $relation, $property) {
            $this->relationConstraints[] = $property = $query->getModel()->getTable().'.'.$property;

            parent::__invoke($query, $value, $property);
        });
    }
}
