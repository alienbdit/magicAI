<?php

namespace App\Http\Controllers\Api;

use App\Domains\Engine\Enums\EngineEnum;
use App\Extensions\Chatbot\System\Http\Requests\ChatbotCustomizeRequest;
use App\Extensions\Chatbot\System\Http\Requests\ChatbotStoreRequest;
use App\Extensions\Chatbot\System\Http\Requests\Train\TrainUrlRequest;
use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotEmbeddingResource;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotResource;
use App\Extensions\Chatbot\System\Parsers\LinkParser;
use App\Extensions\Chatbot\System\Services\ChatbotService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Extensions\Chatbot\System\Models\Chatbot;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class ChatBotController extends Controller
{
    public function __construct(public ChatbotService $service) {}
    public function index()
    {
        $data=Chatbot::all();
        return response()->json($data);
    }
    public function singleChatBot($id)
    {
        $data=Chatbot::findorfail($id);
        return response()->json($data);
    }

    public function create(ChatbotStoreRequest $request): JsonResponse|\App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotResource
    {
        $chatbot = $this->service->query()->create($request->validated());

        return response()->json($chatbot);
    }

    public function update(ChatbotCustomizeRequest $request): JsonResponse|\App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotResource
    {
        $data = $request->validated();

        $chatbot = $this->service->update($data['id'], $data);

        return response()->json($chatbot);
    }


    public function trainUrl(TrainUrlRequest $request): JsonResponse|AnonymousResourceCollection
    {

        $chatbot = $this->service->query()->findOrFail($request->validated('id'));

        $chatbot->setAttribute('engine', EngineEnum::OPEN_AI->value);

        app(LinkParser::class)
            ->setBaseUrl($request->validated('url'))
            ->crawl((bool) $request->validated('single'))
            ->insertEmbeddings($chatbot);

        $data= ChatbotEmbeddingResource::collection(
            $chatbot->embeddings()->whereNotNull('url')->get()
        );
        return response()->json($data);
    }

    public function delete(Request $request): JsonResponse
    {

        $request->validate(['id' => 'required']);

        $chatbot = $this->service->query()->findOrFail($request->get('id'));

        if ($chatbot->getAttribute('user_id') === Auth::id()) {
            $chatbot->delete();
        } else {
            abort(403);
        }

        return response()->json([
            'message' => 'Chatbot deleted successfully',
            'type'    => 'success',
            'status'  => 200,
        ]);
    }
    public function getEmbedCode(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required']);
        $chatbot = $this->service->query()->findOrFail($request->get('id'));
        $code='<script defer src="https://magic.chromatics.ai/vendor/chatbot/js/external-chatbot.js" data-chatbot-uuid="'.$chatbot->uuid.'" data-iframe-width="420" data-iframe-height="745" data-language="en" ></script>';
        return response()->json([
            'title'        => $chatbot->title,
            'embedCode'    => $code,
            'status'       => 200,
        ]);
    }
}
