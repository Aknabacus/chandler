<?php

namespace App\Domains\Settings\ManageTemplates\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Template;
use App\Services\BaseService;

class DestroyTemplate extends BaseService implements ServiceInterface
{
    private Template $template;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'template_id' => 'required|integer|exists:templates,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Destroy a template.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->template = $this->account()->templates()
            ->findOrFail($data['template_id']);

        $this->template->delete();
    }
}
