<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event !== null
            && $this->user() !== null
            && $this->user()->can('update', $event);
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['sometimes', 'integer', 'exists:venues,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'event_type' => ['sometimes', 'string', 'max:100'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::enum(EventStatus::class)],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $event = $this->route('event');
            if (! $event) {
                return;
            }

            if ($this->has('is_featured') && $this->user()?->role !== UserRole::ADMIN) {
                $validator->errors()->add('is_featured', 'فقط مسؤولي النظام يمكنهم تمييز الفعالية.');
            }

            $startAt = $this->has('start_at') ? $this->date('start_at') : $event->start_at;
            $endAt = $this->has('end_at') ? $this->date('end_at') : $event->end_at;

            if ($startAt && $endAt && $endAt <= $startAt) {
                $validator->errors()->add('end_at', 'يجب أن يكون تاريخ نهاية الفعالية بعد تاريخ البدء.');
            }

            if ($event->status === EventStatus::CANCELLED && $this->has('status') && $this->input('status') !== EventStatus::CANCELLED->value) {
                $validator->errors()->add('status', 'لا يمكن تعديل حالة فعالية تم إلغاؤها بالفعل.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'venue_id.exists' => 'المكان المحدد غير موجود.',
            'title.max' => 'يجب ألا يتجاوز عنوان الفعالية 255 حرفاً.',
            'end_at.after' => 'يجب أن يكون تاريخ نهاية الفعالية بعد تاريخ البدء.',
            'status.enum' => 'حالة الفعالية المحددة غير صالحة.',
            'is_featured.boolean' => 'حقل تمييز الفعالية يجب أن يكون قيمة منطقية.',
        ];
    }
}
