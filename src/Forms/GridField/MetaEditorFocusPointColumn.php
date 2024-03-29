<?php

namespace Goldfinch\ImageEditor\Forms\GridField;

use SilverStripe\ORM\DB;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\TextField;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use TractorCow\Fluent\Extension\FluentSiteTreeExtension;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

class MetaEditorFocusPointColumn extends GridFieldDataColumns implements
    GridField_ColumnProvider,
    GridField_HTMLProvider,
    GridField_URLHandler
{
    /**
     * Augment Columns
     *
     * @param GridField $gridField Gridfield
     * @param array     $columns   Columns
     *
     * @return null
     */
    public function augmentColumns($gridField, &$columns)
    {
    }

    /**
     * GetColumnsHandled
     *
     * @param GridField $gridField Gridfield
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['MetaEditorFocusPointColumn'];
    }

    /**
     * GetColumnMetaData
     *
     * @param GridField $gridField  Gridfield
     * @param string    $columnName Column name
     *
     * @return array
     */
    public function getColumnMetaData($gridField, $columnName)
    {
        return [
            'title' => 'Meta Title',
        ];
    }

    /**
     * Get column attributes
     *
     * @param GridField  $gridField  Gridfield
     * @param DataObject $record     Record
     * @param string     $columnName Column name
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        // editor permission

        $errors = self::getErrors($record);

        return [
            'class' => count($errors)
                ? 'has-warning image-editor-error ' . implode(' ', $errors)
                : 'has-success',
        ];
    }

    /**
     * Return all the error messages
     *
     * @param DataObject $record DataObject
     *
     * @return string
     */
    public static function getErrors($record)
    {
        $title_field = 'Title';
        $title_min = 2;
        $title_max = 180;

        // editor permission

        $errors = [];

        if (strlen($record->{$title_field}) < $title_min) {
            $errors[] = 'image-editor-error-too-short';
        } elseif (strlen($record->{$title_field}) > $title_max) {
            $errors[] = 'image-editor-error-too-long';
        } elseif (
            $record->{$title_field} &&
            self::getAllEditableRecords()
                ->filter($title_field, $record->{$title_field})
                ->count() > 1
        ) {
            $errors[] = 'image-editor-error-duplicate';
        }

        return $errors;
    }

    /**
     * Get column content
     *
     * @param GridField  $gridField  Gridfield
     * @param DataObject $record     Record
     * @param string     $columnName Column name
     *
     * @return string
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if ('MetaEditorFocusPointColumn' == $columnName) {
            $value = $gridField->getDataFieldValue($record, 'Title');
            // if permissions {

            $title_field = TextField::create('MetaTitle');
            $title_field->setName(
                $this->getFieldName(
                    $title_field->getName(),
                    $gridField,
                    $record,
                ),
            );
            $title_field->setValue($value);
            $title_field->addExtraClass('form-control');

            return $title_field->Field() . $this->getErrorMessages();
            // }

            // return '<span class="non-editable">Meta tags not editable</span>';
        }
    }

    /**
     * Get field name
     *
     * @param string     $name      Name
     * @param GridField  $gridField Gridfield
     * @param DataObject $record    Record
     *
     * @return string
     */
    protected function getFieldName($name, GridField $gridField, $record)
    {
        return sprintf('%s[%s][%s]', $gridField->getName(), $record->ID, $name);
    }

    /**
     * Get HTML Fragment
     *
     * @param GridField $gridField Gridfield
     *
     * @return GridField
     */
    public function getHTMLFragments($gridField)
    {
        $gridField->addExtraClass('image-editor');
    }

    /**
     * Get URL handlers
     *
     * @param GridField $gridField Gridfield
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'update/$ID' => 'handleAction',
        ];
    }

    /**
     * Handle Action
     *
     * @param GridField   $gridField Gridfield
     * @param HTTPRequest $request   HTTP request
     *
     * @return HTTPResponse
     */
    public function handleAction($gridField, $request)
    {
        $data = $request->postVar($gridField->getName());

        $modelClass = $gridField->getList()->dataClass;
        $modelClassDB = (new \ReflectionClass($modelClass))->getShortName();

        $title_field = 'Title';

        $sitetree = $modelClassDB;
        $sitetree_live = $modelClassDB . '_Live';

        $fluent = false;

        if (SiteTree::class == $modelClass) {
            $fluent =
                Injector::inst()
                    ->get(SiteTree::class)
                    ->hasExtension(FluentSiteTreeExtension::class) &&
                Locale::get()->count();

            if ($fluent) {
                $sitetree = 'SiteTree_Localised';
                $sitetree_live = 'SiteTree_Localised_Live';
                $locale = FluentState::singleton()->getLocale();
            }
        }

        foreach ($data as $id => $params) {
            $page = self::getAllEditableRecords()->byID((int) $id);

            $errors = [];

            $identifier = $fluent
                ? "RecordID = {$page->ID} AND Locale = '{$locale}'"
                : "ID = {$page->ID}";

            foreach ($params as $fieldName => $val) {
                $val = trim(preg_replace('/\s+/', ' ', $val));
                if ($val) {
                    $sqlValue = "'" . Convert::raw2sql($val) . "'";
                } else {
                    $sqlValue = 'NULL';
                }

                // Make sure the MenuTitle remains unchanged if NULL!
                if ('Title' == $fieldName || 'Name' == $fieldName) {
                    $sitetree = 'File';
                    $sitetree_live = 'File_Live';

                    // Update MetaDescription
                    DB::query(
                        "UPDATE {$sitetree} SET {$fieldName} = {$sqlValue}
                        WHERE " . $identifier,
                    );

                    if ($page->isPublished()) {
                        DB::query(
                            "UPDATE {$sitetree_live}
                            SET {$fieldName} = {$sqlValue}
                            WHERE " . $identifier,
                        );

                        if ($page->hasMethod('onAfterImageEditorUpdate')) {
                            $page->onAfterImageEditorUpdate();
                        }
                    }

                    $record = self::getAllEditableRecords()->byID($page->ID);
                    $errors = [];

                    return $this->ajaxResponse(
                        $fieldName . ' saved (' . strlen($val) . ' chars)',
                        ['errors' => $errors],
                    );
                } elseif (
                    'FocusPointX' == $fieldName ||
                    'FocusPointY' == $fieldName
                ) {
                    $sitetree = 'Image';
                    $sitetree_live = 'Image_Live';

                    // Update MetaDescription
                    DB::query(
                        "UPDATE {$sitetree} SET {$fieldName} = {$sqlValue}
                        WHERE " . $identifier,
                    );

                    if ($page->isPublished()) {
                        DB::query(
                            "UPDATE {$sitetree_live}
                            SET {$fieldName} = {$sqlValue}
                            WHERE " . $identifier,
                        );

                        if ($page->hasMethod('onAfterImageEditorUpdate')) {
                            $page->onAfterImageEditorUpdate();
                        }
                    }

                    $record = self::getAllEditableRecords()->byID($page->ID);
                    $errors = [];

                    return $this->ajaxResponse(true, ['errors' => $errors]);
                }
            }
        }

        throw new HTTPResponse_Exception('An error occurred while saving', 500);
    }

    /**
     * Ajac response
     *
     * @param string $message Message
     * @param array  $data    Array
     *
     * @return HTTPResponse
     */
    public function ajaxResponse($message, $data = [])
    {
        $response = new HTTPResponse();
        // $response->addHeader(
        //     'X-Status',
        //     rawurlencode($message)
        // );

        $response->setBody(json_encode($data));

        return $response;
    }

    /**
     * Return all editable records
     *
     * @return DataList
     */
    public static function getAllEditableRecords()
    {
        $ignore = [];

        $list = Image::get();

        if (!empty($ignore)) {
            $list = $list->exclude('ClassName', $ignore); // remove error pages etc
        }

        return $list; //->sort('Sort');
    }

    /**
     * Return all the error messages
     *
     * @return string
     */
    public function getErrorMessages()
    {
        $title_min = 2;
        $title_max = 180;

        return '<div class="image-editor-errors">' .
            '<span class="image-editor-message image-editor-message-too-short">' .
            _t(
                self::class . '.TITLE_TOO_SHORT',
                'Too short: should be between {title_min} &amp; {title_max} characters.',
                [
                    'title_min' => $title_min,
                    'title_max' => $title_max,
                ],
            ) .
            '</span>' .
            '<span class="image-editor-message image-editor-message-too-long">' .
            _t(
                self::class . '.TITLE_TOO_LONG',
                'Too long: should be between {title_min} &amp; {title_max} characters.',
                [
                    'title_min' => $title_min,
                    'title_max' => $title_max,
                ],
            ) .
            '</span>' .
            '<span class="image-editor-message image-editor-message-duplicate">' .
            _t(
                self::class . '.TITLE_DUPLICATE',
                'This title is a duplicate of another page.',
            ) .
            '</span>' .
            '</div>';
    }
}
