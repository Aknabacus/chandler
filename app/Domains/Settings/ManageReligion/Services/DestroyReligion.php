<?php

namespace App\Domains\Settings\ManageReligion\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Religion;
use App\Services\BaseService;

class DestroyReligion extends BaseService implements ServiceInterface
{
    private Religion $religion;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'religion_id' => 'required|integer|exists:religions,id',
            'author_id' => 'required|integer|exists:users,id',
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
     * Destroy a religion.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->religion = $this->account()->religions()
            ->findOrFail($data['religion_id']);

        $this->religion->delete();

        $this->repositionEverything();
    }

    private function repositionEverything(): void
    {
        $this->account()->religions()
            ->where('position', '>', $this->religion->position)
            ->decrement('position');
    }
}
