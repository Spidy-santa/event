<?php
header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$options = [];
$base_price = 0;

switch($category) {
    case 'wedding':
        $options = [
            'catering' => [
                'label' => 'Catering Options',
                'type' => 'select',
                'options' => [
                    ['value' => 'basic', 'label' => 'Basic Package (50 guests)', 'price' => 2000],
                    ['value' => 'premium', 'label' => 'Premium Package (100 guests)', 'price' => 4000],
                    ['value' => 'luxury', 'label' => 'Luxury Package (200 guests)', 'price' => 8000]
                ]
            ],
            'flowers' => [
                'label' => 'Floral Arrangements',
                'type' => 'select',
                'options' => [
                    ['value' => 'minimal', 'label' => 'Minimal Set', 'price' => 500],
                    ['value' => 'standard', 'label' => 'Standard Set', 'price' => 1000],
                    ['value' => 'elaborate', 'label' => 'Elaborate Set', 'price' => 2000]
                ]
            ],
            'venue_decoration' => [
                'label' => 'Venue Decoration',
                'type' => 'select',
                'options' => [
                    ['value' => 'basic', 'label' => 'Basic Decoration', 'price' => 1000],
                    ['value' => 'themed', 'label' => 'Themed Decoration', 'price' => 2500],
                    ['value' => 'luxury', 'label' => 'Luxury Decoration', 'price' => 5000]
                ]
            ]
        ];
        $base_price = 1000; // Base price for wedding events
        break;

    case 'conference':
        $options = [
            'hall_type' => [
                'label' => 'Conference Hall Type',
                'type' => 'select',
                'options' => [
                    ['value' => 'standard', 'label' => 'Standard Hall (100 seats)', 'price' => 1000],
                    ['value' => 'premium', 'label' => 'Premium Hall (200 seats)', 'price' => 2000],
                    ['value' => 'executive', 'label' => 'Executive Hall (300 seats)', 'price' => 3500]
                ]
            ],
            'seating' => [
                'label' => 'Seating Arrangement',
                'type' => 'select',
                'options' => [
                    ['value' => 'theater', 'label' => 'Theater Style', 'price' => 500],
                    ['value' => 'classroom', 'label' => 'Classroom Style', 'price' => 800],
                    ['value' => 'roundtable', 'label' => 'Round Table', 'price' => 1000]
                ]
            ],
            'facilities' => [
                'label' => 'Additional Facilities',
                'type' => 'checkbox',
                'options' => [
                    ['value' => 'parking', 'label' => 'Parking Facility', 'price' => 300],
                    ['value' => 'av_system', 'label' => 'AV System', 'price' => 500],
                    ['value' => 'catering', 'label' => 'Catering Service', 'price' => 1500]
                ]
            ]
        ];
        $base_price = 800; // Base price for conference events
        break;

    case 'birthday':
        $options = [
            'theme' => [
                'label' => 'Party Theme',
                'type' => 'select',
                'options' => [
                    ['value' => 'kids', 'label' => 'Kids Theme', 'price' => 500],
                    ['value' => 'teens', 'label' => 'Teens Theme', 'price' => 800],
                    ['value' => 'adult', 'label' => 'Adult Theme', 'price' => 1000]
                ]
            ],
            'food_package' => [
                'label' => 'Food Package',
                'type' => 'select',
                'options' => [
                    ['value' => 'basic', 'label' => 'Basic Package (20 guests)', 'price' => 400],
                    ['value' => 'standard', 'label' => 'Standard Package (50 guests)', 'price' => 800],
                    ['value' => 'premium', 'label' => 'Premium Package (100 guests)', 'price' => 1500]
                ]
            ],
            'entertainment' => [
                'label' => 'Entertainment Options',
                'type' => 'checkbox',
                'options' => [
                    ['value' => 'music', 'label' => 'Live Music', 'price' => 300],
                    ['value' => 'games', 'label' => 'Party Games', 'price' => 200],
                    ['value' => 'photo_booth', 'label' => 'Photo Booth', 'price' => 400]
                ]
            ]
        ];
        $base_price = 500; // Base price for birthday events
        break;
}

echo json_encode([
    'options' => $options,
    'base_price' => $base_price
]);