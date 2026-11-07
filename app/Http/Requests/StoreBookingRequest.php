<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_id.required' => 'حقل الفعالية مطلوب.',
            'event_id.exists' => 'الفعالية المحددة غير موجودة.',
            'items.required' => 'يجب تحديد تذكرة واحدة على الأقل.',
            'items.array' => 'بيانات التذاكر يجب أن تكون في مصفوفة.',
            'items.min' => 'يجب تحديد تذكرة واحدة على الأقل.',
            'items.*.ticket_type_id.required' => 'حقل نوع التذكرة مطلوب.',
            'items.*.ticket_type_id.exists' => 'نوع التذكرة المحدد غير موجود.',
            'items.*.quantity.required' => 'حقل كمية التذاكر مطلوب.',
            'items.*.quantity.min' => 'يجب أن تكون كمية التذاكر على الأقل 1.',
            'items.*.quantity.integer' => 'كمية التذاكر يجب أن تكون رقماً صحيحاً.',
        ];
    }
}
