<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot as Pivot;

class Relationshipable extends Pivot
{
    protected $table = 'relationshipables';

    public function getRelatedModelsFromGivenType($model_type, $giver_id = null, $retrieve_models = false) {
             
        $relationships = [];
        
        $this->giver = $model_type::when(!is_null($giver_id), function ($query) use ($giver_id) {
            return $query->where('id', '=', $giver_id);
        })->whereHas('types', function ($query) {
            $query->where('types.id', $this->relationshipable_id);
        })->get();

        // map giverInstitutions with receiverInstitutions by padalinys_id and receiverType
        $this->giver->map(function ($giver) use (&$relationships, $model_type, $retrieve_models) {
            $giver->receiver = $model_type::whereHas('types', function ($query) {
                $query->where('types.id', $this->related_model_id);
            })->where('padalinys_id', $giver->padalinys_id)->get()->map(function ($receiver) use ($giver, &$relationships, $retrieve_models) {
                
                if ($retrieve_models) {
                    $relationships[] = [
                        'giver_model' => $giver,
                        'receiver_model' => $receiver,
                    ];
                } else {
                    $relationships[] = [
                        'relationshipable_id' => $giver->id,
                        'related_model_id' => $receiver->id,
                    ];
                }
            });
        });

        return $relationships;
    }

    public function getRelatedModelsFromReceiverType($model_type, $receiver_id = null, $retrieve_models = false) {
             
        $relationships = [];
        
        $this->receiver = $model_type::when(!is_null($receiver_id), function ($query) use ($receiver_id) {
            return $query->where('id', '=', $receiver_id);
        })->whereHas('types', function ($query) {
            $query->where('types.id', $this->related_model_id);
        })->get();

        // map giverInstitutions with receiverInstitutions by padalinys_id and receiverType
        $this->receiver->map(function ($receiver) use (&$relationships, $model_type, $retrieve_models) {
            $receiver->giver = $model_type::whereHas('types', function ($query) {
                $query->where('types.id', $this->relationshipable_id);
            })->where('padalinys_id', $receiver->padalinys_id)->get()->map(function ($giver) use ($receiver, &$relationships, $retrieve_models) {
                
                if ($retrieve_models) {
                    $relationships[] = [
                        'giver_model' => $giver,
                        'receiver_model' => $receiver,
                    ];
                } else {
                    $relationships[] = [
                        'relationshipable_id' => $giver->id,
                        'related_model_id' => $receiver->id,
                    ];
                }
            });
        });

        return $relationships;
    }

    public function relationship() {
        return $this->belongsTo(Relationship::class);
    }

    public function relationshipable() {
        return $this->morphTo();
    }

    public function relatedRelationshipable() {
        return $this->morphTo('related_model', 'relationshipable_type');
    }
}
