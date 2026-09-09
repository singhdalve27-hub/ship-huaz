<?php

namespace App\Http\Controllers;

use App\Models\ChatBotNode;
use App\Models\EventType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatBotNodeController extends Controller
{
    public function index()
    {
        $data = [
            'chatBotNodes' => ChatBotNode::latest()->get(),
            'eventTypes' => EventType::where('status', 'active')->get(['id', 'type']), // <-- Ipapasa sa Admin UI
        ];

        return Inertia::render('Admin/ChatNodes', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'node_key' => 'required|min:3|unique:chat_bot_nodes,node_key',
            'message' => 'required|min:2',
            'dynamic_content' => 'nullable|string', // <-- Binago para tumanggap ng specific event ID
        ]);

        ChatBotNode::create($request->only(['node_key', 'message', 'dynamic_content']));

        return redirect()->route('admin.chat-nodes.index')->with('success', 'Chat Node created successfully.');
    }

    public function update(Request $request, ChatBotNode $chatBotNode)
    {
        $request->validate([
            'node_key' => 'required|min:3|unique:chat_bot_nodes,node_key,' . $chatBotNode->id,
            'message' => 'required|min:2',
            'status' => 'required|in:active,inactive',
            'dynamic_content' => 'nullable|string', 
        ]);

        $chatBotNode->update($request->only(['node_key', 'message', 'status', 'dynamic_content']));

        return redirect()->route('admin.chat-nodes.index')->with('success', 'Chat Node updated successfully.');
    }

    public function destroy(ChatBotNode $chatBotNode)
    {
        $chatBotNode->allOptions()->delete();
        $chatBotNode->delete();

        return redirect()->route('admin.chat-nodes.index')->with('success', 'Chat Node deleted successfully.');
    }
}