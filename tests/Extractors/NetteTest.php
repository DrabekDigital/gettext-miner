<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Extractors;

use DrabekDigital\GettextMiner\Extractors\Nette;
use PHPUnit\Framework\TestCase;

class NetteTest extends TestCase
{
    public function testCommon(): void
    {
        $filter = new Nette();
        $document = <<<'EOD'
<?php
$form
->setText('My text')
->setEmptyValue('Empty value')
->setValue('Value')
->addButton('name', 'Button')
->addCheckbox('name', 'Checkbox')
->addCheckboxList('name', 'Checkbox list')
->addError('Error')
->addUpload('name', 'Upload')
->addMultiUpload('name', 'Multi upload')
->addGroup('Group')
->addImage('name', 'Image')
->addPassword('name', 'Password')
->addProxy('name', 'Proxy')
->addRadioList('name', 'Radio list')
->addRule('condition', 'Rule')
->addSelect('name', 'Select')
->addMultiSelect('name', 'Multi select')
->addSubmit('name', 'Submit')
->addText('name', 'Text')
->addTextArea('name', 'Textarea');
->setPrompt('Prompt')
EOD;
        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('My text', $output);
        $this->assertArrayHasKey('Empty value', $output);
        $this->assertArrayHasKey('Value', $output);
        $this->assertArrayHasKey('Button', $output);
        $this->assertArrayHasKey('Checkbox', $output);
        $this->assertArrayHasKey('Checkbox list', $output);
        $this->assertArrayHasKey('Error', $output);
        $this->assertArrayHasKey('Upload', $output);
        $this->assertArrayHasKey('Multi upload', $output);
        $this->assertArrayHasKey('Group', $output);
        $this->assertArrayHasKey('Image', $output);
        $this->assertArrayHasKey('Password', $output);
        $this->assertArrayHasKey('Proxy', $output);
        $this->assertArrayHasKey('Radio list', $output);
        $this->assertArrayHasKey('Rule', $output);
        $this->assertArrayHasKey('Select', $output);
        $this->assertArrayHasKey('Multi select', $output);
        $this->assertArrayHasKey('Submit', $output);
        $this->assertArrayHasKey('Text', $output);
        $this->assertArrayHasKey('Textarea', $output);
        $this->assertArrayHasKey('Prompt', $output);
        $this->assertCount(21, $output);
    }
}
