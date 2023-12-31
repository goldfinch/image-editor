<?php

namespace Goldfinch\ImageEditor\Extensions;

use SilverStripe\ORM\DataExtension;
use Goldfinch\ImageEditor\Forms\ImageCoordsField;

class ImageExtension extends DataExtension
{
    public function getFocusPointGridEditableColumn($columnName, $gridPrefix)
    {
        $record = $this->owner;

        return ImageCoordsField::create(
            $columnName,
            $gridPrefix . '[' . $columnName . 'X' . ']',
            $gridPrefix . '[' . $columnName . 'Y' . ']',
            'filename',
            $record,
            $record->getWidth(),
            $record->getHeight(),
            false,
            true,
        );
    }
}
