<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique(Category::class)->ignore($this->route('category')?->id),
            ],
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $category = $this->route('category');
                $parentId = $this->input('parent_id');
                if (! $category || ! $parentId) {
                    return;
                }

                if ((int) $parentId === $category->id || in_array((int) $parentId, $category->descendantIds(), true)) {
                    $validator->errors()->add('parent_id', 'A category cannot be moved under itself or one of its subcategories.');
                }
            },
        ];
    }
}
