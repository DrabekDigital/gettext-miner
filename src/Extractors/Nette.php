<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

/**
 * Extends PHP extractor to support Nette framework functions that translate internally
 */
class Nette extends PHP
{
    public function __construct(
        array $extraFunctions = [],
        private array $functions = [
            'translate' => 1,
            '_'         => 1
        ],
        private readonly array $extensions = ['.php'],
    ) {
        $extendedFunctions = array_merge(
            $this->functions,
            [
                'setText' => 1,
                'setEmptyValue' => 1,
                'setValue' => 1,
                'addButton' => 2,
                'addCheckbox' => 2,
                'addCheckboxList' => 2,
                'addError' => 1,
                'addUpload' => 2,
                'addMultiUpload' => 2,
                'addGroup' => 1,
                'addImage' => 2,
                'addPassword' => 2,
                'addProxy' => 2,
                'addRadioList' => 2,
                'addRule' => 2,
                'addSelect' => 2,
                'addMultiSelect' => 2,
                'addSubmit' => 2,
                'addText' => 2,
                'addTextArea' => 2,
                'addDatePicker' => 2,
                'setPrompt' => 1,
                'setRequired' => 1,
            ]
        );
        parent::__construct(
            $extraFunctions,
            $extendedFunctions,
            $this->extensions,
        );
    }
}
