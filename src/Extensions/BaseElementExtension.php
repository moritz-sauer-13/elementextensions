<?php

namespace BaseElement\Extensions;

use DNADesign\ElementalVirtual\Model\ElementVirtual;
use HeroElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use TextFormatter\TextFormatter;

class BaseElementExtension extends Extension
{
    private static $db = [
        'SpaceTop' => 'Boolean(1)',
        'SpaceBottom' => 'Boolean(1)',
        'BorderTop' => 'Boolean(0)',
        'BorderBottom' => 'Boolean(0)',
        'HeadlineTag' => 'Varchar(255)',
        'OpticalHeadline' => 'Varchar(255)',
    ];

    private static $inline_editable = false;

    private static $defaults = [
        'SpaceTop' => true,
        'SpaceBottom' => true,
        'BorderTop' => false,
        'BorderBottom' => false,
        'HeadlineTag' => 'h2',
        'OpticalHeadline' => '',
    ];

    private static array $classes_without_spacing = [
        ElementVirtual::class
    ];

    private static array $classes_without_borders = [
        ElementVirtual::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'SpaceTop',
            'SpaceBottom',
            'BorderTop',
            'BorderBottom',
            'HeadlineTag',
            'OpticalHeadline',
        ]);

        $fields->insertAfter('Title', LiteralField::create(
            'TitleHint',
            '<div class="field mb-5">' . TextFormatter::getFormattingDescription() . '</div>'
        ));

        if (
            Config::inst()->get(self::class, 'has_spacing') &&
            !in_array($this->owner->ClassName, Config::inst()->get(self::class, 'classes_without_spacing'))
        ) {
            $fields->addFieldToTab('Root.Settings', Tab::create('Spacing', 'Abstände'));
            $fields->addFieldsToTab('Root.Settings.Spacing', [
                CheckboxField::create('SpaceTop', 'Abstand oben'),
                CheckboxField::create('SpaceBottom', 'Abstand unten'),
            ]);
        }

        if (
            Config::inst()->get(self::class, 'has_borders') &&
            !in_array($this->owner->ClassName, Config::inst()->get(self::class, 'classes_without_borders'))
        ) {
            $fields->addFieldToTab('Root.Settings', Tab::create('Borders', 'Trennlinien'));
            $fields->addFieldsToTab('Root.Settings.Borders', [
                CheckboxField::create('BorderTop', 'Trennlinie oben'),
                CheckboxField::create('BorderBottom', 'Trennlinie unten'),
            ]);
        }

        if (
            Config::inst()->get(self::class, 'has_headline_tags') ||
            Config::inst()->get(self::class, 'has_headline_optic')
        ) {
            $fields->addFieldToTab('Root.Settings', Tab::create('FontSettings', 'Schrifteinstellung'));

            $fontFields = [];

            if (Config::inst()->get(self::class, 'has_headline_tags')) {
                $fontFields[] = DropdownField::create('HeadlineTag', 'Strukturelle Headline (SEO-relevant)', [
                    'h2' => 'H2 (Standard)',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                ])->setDescription('Diese Headline wird als HTML-Tag für SEO verwendet.');
            }

            if (Config::inst()->get(self::class, 'has_headline_optic')) {
                $fontFields[] = DropdownField::create('OpticalHeadline', 'Optische Anpassung (Styling-Klasse, optional)', [
                    '' => 'Keine optische Anpassung',
                    'h1' => 'H1-Optik',
                    'h2' => 'H2-Optik',
                    'h3' => 'H3-Optik',
                    'h4' => 'H4-Optik',
                    'h5' => 'H5-Optik',
                    'h6' => 'H6-Optik',
                ])->setDescription('Falls gewünscht, kann die Headline optisch wie eine andere Größe aussehen.')
                    ->setEmptyString('Keine optische Anpassung');
            }

            $fields->addFieldsToTab('Root.Settings.FontSettings', $fontFields);
        }
    }

    public function ExtraClass()
    {
        $classes = [];

        $baseClass = $this->owner->getField('ExtraClass');
        if ($baseClass) {
            $classes[] = $baseClass;
        }

        if (
            Config::inst()->get(self::class, 'has_spacing') &&
            !in_array($this->owner->ClassName, Config::inst()->get(self::class, 'classes_without_spacing'))
        ) {
            if ($this->owner->getField('SpaceTop')) {
                $classes[] = 'space-top';
            }

            if ($this->owner->getField('SpaceBottom')) {
                $classes[] = 'space-bottom';
            }
        }

        if (Config::inst()->get(self::class, 'has_borders')) {
            if ($this->owner->getField('BorderTop')) {
                $classes[] = 'border-top';
            }

            if ($this->owner->getField('BorderBottom')) {
                $classes[] = 'border-bottom';
            }
        }

        return implode(' ', $classes);
    }

    public function FrontendTitle()
    {
        $title = $this->owner->Title;
        if (!$title) {
            return '';
        }

        return TextFormatter::formattedText($this->generateFrontendHeadlineHTML($title));
    }

    public function generateFrontendHeadlineHTML(string $content): string
    {
        $tag = Config::inst()->get(self::class, 'has_headline_tags')
            ? ($this->owner->HeadlineTag ?: 'h2')
            : 'h2';

        $opticalClass = Config::inst()->get(self::class, 'has_headline_optic')
            ? $this->owner->OpticalHeadline
            : '';

        $classAttr = $opticalClass ? ' class="' . htmlspecialchars($opticalClass) . '"' : '';

        return sprintf('<%1$s%3$s>%2$s</%1$s>', $tag, $content, $classAttr);
    }
}
