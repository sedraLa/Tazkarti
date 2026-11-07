<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->role === UserRole::ADMIN || $user->organizer !== null);
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_type' => ['required', 'string', 'max:100'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'status' => ['sometimes', Rule::enum(EventStatus::class)],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('is_featured') && $this->boolean('is_featured') && $this->user()?->role !== UserRole::ADMIN) {
                $validator->errors()->add('is_featured', 'فقط مسؤولي النظام يمكنهم تمييز الفعالية.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'venue_id.required' => 'حقل المكان مطلوب.',
            'venue_id.exists' => 'المكان المحدد غير موجود.',
            'title.required' => 'حقل عنوان الفعالية مطلوب.',
            'title.max' => 'يجب ألا يتجاوز عنوان الفعالية 255 حرفاً.',
            'description.required' => 'حقل وصف الفعالية مطلوب.',
            'event_type.required' => 'حقل نوع الفعالية مطلوب.',
            'start_at.required' => 'حقل تاريخ ووقت بدء الفعالية مطلوب.',
            'start_at.after' => 'يجب أن يكون تاريخ بدء الفعالية في المستقبل.',
            'end_at.required' => 'حقل تاريخ ووقت نهاية الفعالية مطلوب.',
            'end_at.after' => 'يجب أن يكون تاريخ نهاية الفعالية بعد تاريخ البدء.',
            'status.enum' => 'حالة الفعالية المحددة غير صالحة.',
            'is_featured.boolean' => 'حقل تمييز الفعالية يجب أن يكون قيمة منطقية.',
        ];
    }
}
