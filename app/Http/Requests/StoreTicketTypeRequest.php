<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'quantity' => ['required', 'integer', 'min:1'],
            'max_per_order' => ['required', 'integer', 'min:1', 'lte:quantity'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل اسم نوع التذكرة مطلوب.',
            'category.required' => 'حقل فئة التذكرة مطلوب.',
            'category.enum' => 'فئة التذكرة المحددة غير صالحة.',
            'price.required' => 'حقل السعر مطلوب.',
            'price.numeric' => 'يجب أن يكون السعر قيمة عددية.',
            'price.min' => 'يجب ألا يقل السعر عن صفر.',
            'currency.required' => 'حقل العملة مطلوب.',
            'currency.size' => 'يجب أن تتكون العملة من 3 أحرف.',
            'quantity.required' => 'حقل الكمية مطلوب.',
            'quantity.min' => 'يجب ألا تقل الكمية عن 1.',
            'max_per_order.required' => 'حقل الحد الأقصى لكل طلب مطلوب.',
            'max_per_order.min' => 'يجب ألا يقل الحد الأقصى لكل طلب عن 1.',
            'max_per_order.lte' => 'يجب ألا يتجاوز الحد الأقصى لكل طلب إجمالي الكمية المتاحة.',
        ];
    }
}
