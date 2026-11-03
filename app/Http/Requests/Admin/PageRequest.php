<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isSuperadmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ];
    }
}
