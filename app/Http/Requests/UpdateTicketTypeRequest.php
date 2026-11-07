<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticketType = $this->route('ticketType') ?? $this->route('ticket_type');

        return $ticketType !== null
            && $this->user() !== null
            && $this->user()->can('update', $ticketType);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', Rule::enum(TicketCategory::class)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'max_per_order' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ticketType = $this->route('ticketType') ?? $this->route('ticket_type');
            $event = $this->route('event');

            if ($ticketType && $event && $ticketType->event_id !== $event->id) {
                $validator->errors()->add('ticket_type', 'نوع التذكرة لا ينتمي إلى الفعالية المحددة.');
            }

            if ($ticketType && $this->has('quantity')) {
                $newQty = (int) $this->input('quantity');
                $allocated = (int) ($ticketType->reserved_quantity + $ticketType->sold_quantity);
                if ($newQty < $allocated) {
                    $validator->errors()->add(
                        'quantity',
                        'لا يمكن تقليل الكمية إلى أقل من مجموع التذاكر المحجوزة والمباعة (' . $allocated . ').'
                    );
                }
            }

            if ($ticketType && $this->has('max_per_order')) {
                $targetQty = $this->has('quantity') ? (int) $this->input('quantity') : $ticketType->quantity;
                if ((int) $this->input('max_per_order') > $targetQty) {
                    $validator->errors()->add('max_per_order', 'يجب ألا يتجاوز الحد الأقصى لكل طلب إجمالي الكمية.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'category.enum' => 'فئة التذكرة المحددة غير صالحة.',
            'price.numeric' => 'يجب أن يكون السعر قيمة عددية.',
            'price.min' => 'يجب ألا يقل السعر عن صفر.',
            'currency.size' => 'يجب أن تتكون العملة من 3 أحرف.',
            'quantity.min' => 'يجب ألا تقل الكمية عن 1.',
            'max_per_order.min' => 'يجب ألا يقل الحد الأقصى لكل طلب عن 1.',
        ];
    }
}
