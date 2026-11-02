<?php

namespace App\Http\Livewire;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente para administrar las categorías financieras.
 */
class Categories extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $search;

    public $sort = 'name';

    public $direction = 'ASC';

    public $show_inactive = false;

    public $open = false;

    public $selected_id;

    public $name;

    public $description;

    public function mount(): void
    {
        $this->selected_id = 0;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'name')->whereNull('deleted_at')->ignore($this->selected_id ?: null),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowInactive(): void
    {
        $this->resetPage();
    }

    public function order($sort): void
    {
        $allowed = ['id', 'name', 'system_key', 'is_active'];

        if (! in_array($sort, $allowed, true)) {
            return;
        }

        if ($this->sort == $sort) {
            $this->direction = $this->direction == 'DESC' ? 'ASC' : 'DESC';
        } else {
            $this->sort = $sort;
            $this->direction = 'ASC';
        }
    }

    public function updatingOpen(): void
    {
        if (! $this->open) {
            return;
        }

        $this->resetUI();
    }

    public function resetUI(): void
    {
        $this->reset(['name', 'description']);
        $this->open = false;
        $this->selected_id = 0;
    }

    public function updated($propertyName): void
    {
        $rules = $this->rules();

        if (! array_key_exists($propertyName, $rules)) {
            return;
        }

        $this->validateOnly($propertyName, [$propertyName => $rules[$propertyName]]);
    }

    public function render()
    {
        $search = (string) $this->search;
        $sort = in_array($this->sort, ['id', 'name', 'system_key', 'is_active'], true)
            ? $this->sort
            : 'name';
        $direction = $this->direction === 'ASC' ? 'ASC' : 'DESC';

        $categories = Category::query()
            ->when(! $this->show_inactive, fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10);

        return view('livewire.categories.categories', compact('categories'));
    }

    public function save(): void
    {
        $validatedData = $this->validate($this->rules());

        Category::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        $this->resetUI();
        $this->alert('success', 'Categoría registrada satisfactoriamente');
    }

    public function edit($id): void
    {
        $category = Category::find($id);

        if (! $category) {
            $this->alert('error', 'La categoría seleccionada ya no existe.');

            return;
        }

        $this->name = $category->name;
        $this->description = $category->description;
        $this->selected_id = $category->id;
        $this->open = true;
    }

    public function update(): void
    {
        $validatedData = $this->validate($this->rules());
        $category = Category::find($this->selected_id);

        if (! $category) {
            $this->alert('error', 'La categoría seleccionada ya no existe.');
            $this->resetUI();

            return;
        }

        $category->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
        ]);

        $this->resetUI();
        $this->alert('success', 'Categoría modificada satisfactoriamente');
    }

    public function archive($id): void
    {
        $category = Category::find($id);

        if (! $category) {
            $this->alert('error', 'La categoría seleccionada ya no existe.');

            return;
        }

        if ($category->isSystem()) {
            $this->alert('error', 'Las categorías internas del sistema no se pueden archivar.');

            return;
        }

        if ($category->subscriptions()->exists()) {
            $this->alert('error', 'La categoría tiene suscripciones asociadas. Elimina o cambia esas suscripciones antes de archivarla.');

            return;
        }

        $category->update(['is_active' => false]);
        $this->alert('success', 'Categoría archivada satisfactoriamente');
    }

    public function restore($id): void
    {
        $category = Category::find($id);

        if (! $category) {
            $this->alert('error', 'La categoría seleccionada ya no existe.');

            return;
        }

        $category->update(['is_active' => true]);
        $this->alert('success', 'Categoría restaurada satisfactoriamente');
    }
}
