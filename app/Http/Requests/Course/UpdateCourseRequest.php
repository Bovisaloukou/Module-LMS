<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'level' => ['nullable', Rule::enum(CourseLevel::class)],
            'language' => ['nullable', 'string', 'max:10'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string'],
            'what_you_learn' => ['nullable', 'array'],
            'what_you_learn.*' => ['string'],
        ];
    }
}
