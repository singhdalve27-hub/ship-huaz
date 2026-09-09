<?php

namespace App\Http\Controllers;

use App\Models\ChatBotNode;
use App\Models\EventType;
use App\Models\VenuePackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatBotController extends Controller
{
    public function index(): JsonResponse
    {
        $nodes = ChatBotNode::with('options')
            ->where('status', 'active')
            ->get();

        $eventTypes = EventType::where('status', 'active')->get(['id', 'type']);
        
        // BAGO: Isinama natin ang 'image' sa kukunin mula sa database
        $venuePackages = VenuePackage::where('status', 'active')->get([
            'id', 'title', 'description', 'guests', 'price', 
            'price_morning', 'price_afternoon', 'price_fullday', 'price_visitor',
            'event_type_id', 'image' 
        ]);

        $mapped = [];
        $mainNodeId = null;

        foreach ($nodes as $node) {
            $images = $node->images ? json_decode($node->images) : [];

            $dynamicData = null;
            if ($node->dynamic_content === 'event_types') {
                $dynamicData = [
                    'type' => 'event_types',
                    'items' => $eventTypes
                ];
            } elseif (str_starts_with($node->dynamic_content ?? '', 'venue_packages_')) {
                $evtId = str_replace('venue_packages_', '', $node->dynamic_content);
                $filteredPackages = $venuePackages->where('event_type_id', $evtId)->values();

                $dynamicData = [
                    'type' => 'venue_packages',
                    'items' => $filteredPackages
                ];
            }

            $mapped[$node->id] = [
                'id'           => $node->id,
                'node_key'     => $node->node_key,
                'message'      => $node->message,
                'images'       => $images,
                'dynamic_data' => $dynamicData, 
                'options'      => $node->options->map(fn($opt) => [
                    'label'        => $opt->option['label'] ?? '',
                    'next_node_id' => $opt->option['next_node_id'] ?? 0,
                ])->values()->toArray(),
            ];

            if (strtolower($node->node_key) === 'main') {
                $mainNodeId = $node->id;
            }
        }

        return response()->json([
            'nodes'        => $mapped,
            'main_node_id' => $mainNodeId,
        ]);
    }

    public function nodes(): JsonResponse
    {
        $nodes = ChatBotNode::where('status', 'active')
            ->orderBy('node_key')
            ->get(['id', 'node_key']);

        return response()->json($nodes);
    }
}