<?php

namespace Wotz\BelongsToMany\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Livewire\Component;

class BelongsToManyInput extends Field
{
    protected string $view = 'belongs-to-many-field::forms.components.belongs-to-many-input';

    public string|Closure $displayViewUsing = 'belongs-to-many-field::table-item';

    public string|Closure $relationship;

    public int|Closure $pagination = 10;

    public string|Closure $itemLabel = 'id';

    public bool|string|Closure $sortable = false;

    public Closure $resourceQuery;

    public function setUp(): void
    {
        // Guess the relationship name
        $this->relationship = $this->getName();

        // Default to all items
        $this->resourceQuery(function (Builder $query) {
            return $query;
        });

        // Get the selected items
        $this->loadStateFromRelationshipsUsing(static function (self $component): void {
            $relationship = $component->getRelationship();
            $sortable = $component->getSortable();

            /** @var \Illuminate\Database\Eloquent\Collection $results */
            $results = $relationship->getResults();

            $state = $results
                ->when(is_string($sortable), fn ($query) => $query->sortBy("pivot.{$sortable}"))
                ->pluck($relationship->getRelatedKeyName())
                ->toArray();

            $component->state($state);
        });

        // Save the newly selected items
        $this->saveRelationshipsUsing(static function (self $component, $state) {
            $state = Collection::wrap($state ?? []);
            $sortable = $component->getSortable();
            if (is_string($sortable)) {
                $state = $state->mapWithKeys(function ($item, $index) use ($sortable) {
                    return [$item => [$sortable => $index + 10000]];
                });
            }

            $component->getRelationship()->sync(
                $state->toArray(),
                true
            );

            $livewire = $component->getLivewire();

            $livewire->dispatch("belongs-to-many::resetSelected-{$component->getStatePath()}");
        });

        // Don't save the state as a normal field
        $this->dehydrated(false);
    }

    public function resourceQuery(Closure $callback): self
    {
        $this->resourceQuery = $callback;

        return $this;
    }

    public function getResources(): Collection
    {
        $related = $this->getRelationship()->getRelated();
        $query = $this->evaluate($this->resourceQuery, ['query' => $related->query()]);

        return collect($query->get());
    }

    public function getResourcesForAlpine(): Collection
    {
        return $this->getResources()->map(fn ($item) => [
            'id' => $item->id,
            'selected' => in_array($item->id, $this->getState() ?? []),
            'html' => view($this->getDisplayUsingView(), [
                'item' => $item,
                'label' => $this->getDisplayLabelUsing($item),
            ])->render(),
        ]);
    }

    public function relationship(string|Closure $relationship): self
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function getRelationship(): BelongsToMany
    {
        return $this->getModelInstance()->{$this->evaluate($this->relationship)}();
    }

    public function sortable(bool|string|Closure $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function getSortable(): bool|string
    {
        return $this->evaluate($this->sortable);
    }

    public function pagination(bool|int|Closure $callback): static
    {
        if (is_bool($callback)) {
            $callback = $callback ? $this->pagination : PHP_INT_MAX;
        }

        $this->pagination = $callback;

        return $this;
    }

    public function getPagination()
    {
        return $this->evaluate($this->pagination);
    }

    public function displayViewUsing(string|Closure $view): self
    {
        $this->displayViewUsing = $view;

        return $this;
    }

    public function getDisplayUsingView(): string
    {
        return $this->evaluate($this->displayViewUsing);
    }

    public function displayLabelUsing(null|string|Closure $view): self
    {
        $this->itemLabel = $view;

        return $this;
    }

    public function getDisplayLabelUsing($item)
    {
        if (is_string($this->itemLabel)) {
            return $item->{$this->itemLabel} ?? $item->id;
        }

        return $this->evaluate($this->itemLabel, ['item' => $item]);
    }

    #[ExposedLivewireMethod]
    public function fetchItems(): void
    {
        /** @var Component&HasForms $livewire */
        $livewire = $this->getLivewire();

        $livewire->dispatch(
            "belongs-to-many::itemsFetchedFor-{$this->getStatePath()}",
            $this->getResourcesForAlpine()
        );
    }
}
