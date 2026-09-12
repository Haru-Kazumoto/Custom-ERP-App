<?php

namespace App\Modules\Account\Queries;

use App\Modules\Account\Repositories\AccountRepository;

class GetAccountQuery
{
    public function __construct(private AccountRepository $repository)
    {}

    public function execute(int $user_id)
    {
        return $this->repository->getInformationAccountById($user_id);
    }
}
