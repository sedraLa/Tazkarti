<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEventAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event && $this->user()?->can('sendAnnouncement', $event);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'نص الإعلان مطلوب.',
            'message.string' => 'نص الإعلان يجب أن يكون نصاً.',
            'message.min' => 'نص الإعلان يجب ألا يقل عن 5 أحرف.',
            'message.max' => 'نص الإعلان يجب ألا يتجاوز 5000 حرف.',
            'title.string' => 'عنوان الإعلان يجب أن يكون نصاً.',
            'title.max' => 'عنوان الإعلان يجب ألا يتجاوز 255 حرفاً.',
        ];
    }
}
