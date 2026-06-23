<?php

namespace App\DTOs;

use App\Enums\CommunicationModel;
use App\Http\Requests\Backoffice\CreateTenantRequest;

final readonly class CreateTenantData
{
    public function __construct(
        public string            $name,
        public string            $document,
        public string            $email,
        public ?string           $phone,
        public CommunicationModel $communicationModel,
        public ?string           $notes,
    ) {}

    public static function fromRequest(CreateTenantRequest $request): self
    {
        return new self(
            name:               $request->name,
            document:           preg_replace('/\D/', '', $request->document),
            email:              $request->email,
            phone:              $request->phone,
            communicationModel: CommunicationModel::from($request->communication_model),
            notes:              $request->notes,
        );
    }
}
