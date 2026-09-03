<?php

namespace App\Http\Controllers\Economy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\TransferRequest;
use App\Services\Economy\Protocols\TransferProtocol;

class TransferController extends Controller
{
    public function __construct(
        private TransferProtocol $transferProtocol
    ) {}

    public function store(TransferRequest $request)
    {
        return $this->transferProtocol->execute(
            $request->validated()
        );
    }
}