<?php

namespace Goldfinch\ImageEditor\Extensions;

use SilverStripe\ORM\DataExtension;
use Goldfinch\ImageEditor\Forms\ImageCoordsField;

class FileFocusPointExtension extends DataExtension
{
    public function updateCMSFields($fields)
    {
        // &$fields?
        if ($this->owner->appCategory() === 'image') {
            $field = ImageCoordsField::create(
                'FocusPoint',
                'FocusPointX',
                'FocusPointY',
                'filename',
                $this->owner,
                $this->owner->getWidth(),
                $this->owner->getHeight(),
            );

            // $field = FocusPointField::create('FocusPoint', $this->owner->fieldLabel('Focus point'), $this->owner);

            $fields->insertAfter('Title', $field);
        }
    }
}
