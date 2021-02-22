<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\Utils\Context;
use Hyperf\Validation\Request\FormRequest;
use Psr\Http\Message\ResponseInterface;
use Rych\Bencode\Bencode;

class AnnounceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'info_hash' => 'required|size:20',
            'peer_id' => 'required|size:20',
            'port' => 'required|integer',
            'uploaded' => 'integer',
            'downloaded' => 'integer',
            'left' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'size' => Bencode::encode(['failure reason' => ':attribute 信息有误']),
            'required' => Bencode::encode(['failure reason' => ':attribute 信息有误']),
            'integer' => Bencode::encode(['failure reason' => ':attribute 信息有误']),
        ];
    }
}
