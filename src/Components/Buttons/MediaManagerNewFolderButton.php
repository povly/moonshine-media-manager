<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Components\Buttons;

use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Text;

/**
 * @method static static make()
 */
final class MediaManagerNewFolderButton extends ActionButton
{
    public function __construct()
    {
        parent::__construct(__('moonshine-media-manager::media-manager.new_folder'), route('moonshine.media.manager.new.folder'));

        $this->inModal(
            __('moonshine-media-manager::media-manager.new_folder'),
            fn(mixed $data): string => (string)FormBuilder::make(
                $this->getUrl($data),
            )
                ->fields([
                    Hidden::make('dir'),
                    Text::make(__('moonshine-media-manager::media-manager.name'), 'name'),
                ])
                ->fill([
                    'dir' => moonshineRequest()->get('path', '/'),
                ])
                ->submit(__('moonshine-media-manager::media-manager.submit')),
        )
            ->icon('folder')
            ->secondary()
            ->showInLine();
    }
}
