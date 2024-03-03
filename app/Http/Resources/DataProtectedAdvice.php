<?php

namespace App\Http\Resources;

use App\Models\Advice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class DataProtectedAdvice extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $email = $this->email;
        $phone = $this->phone;
        if(! Auth::user()->can('view', Advice::findOrFail($this->id))){
            $email = null;
            $phone = null;
        }
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'street' => $this->street,
            'streetNumber' => $this->streetNumber,
            'zip' => $this->zip,
            'city' => $this->city,
            'email' => $email,
            'phone' => $phone,
            'commentary' => $this->commentary,
            'advisor_id' => $this->advisor_id,
            'advice_status_id' => $this->advice_status_id,
            'long' => $this->long,
            'lat' => $this->lat,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'distance' => $this->distance,
            'shares_ids' => $this->shares_ids,
            'placeNotes' => $this->placeNotes,
            'houseType' => $this->houseType,
            'landlordExists' => $this->landlordExists,
            'helpType_place' => $this->helpType_place,
            'helpType_technical' => $this->helpType_technical,
            'helpType_bureaucracy' => $this->helpType_bureaucracy,
            'helpType_other' => $this->helpType_other,
            'placeNotes' => $this->placeNotes,
        ];
    }
}
