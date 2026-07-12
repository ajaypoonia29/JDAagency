<?php

namespace App\Support\Forms\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SocialMediaSection
{
    public static function make(): Section
    {
        return Section::make('Social Media')
            ->description('Official social media profiles.')
            ->icon('heroicon-o-globe-alt')
            ->collapsible()
            ->schema([

                Grid::make(2)
                    ->schema([

                        TextInput::make('facebook')
                            ->label('Facebook URL')
                            ->url(),

                        TextInput::make('instagram')
                            ->label('Instagram URL')
                            ->url(),

                        TextInput::make('linkedin')
                            ->label('LinkedIn URL')
                            ->url(),

                        TextInput::make('youtube')
                            ->label('YouTube URL')
                            ->url(),

                        TextInput::make('twitter')
                            ->label('X / Twitter URL')
                            ->url(),

                    ]),

            ]);
    }
}