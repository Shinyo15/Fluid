<?php

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\Format;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\Fixtures\UserWithoutToString;
use TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\Fixtures\UserWithToString;
use TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper;

class HtmlspecialcharsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var HtmlspecialcharsViewHelper&MockObject
     */
    protected $viewHelper;

    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMock(HtmlspecialcharsViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperInitializesArguments()
    {
        $this->viewHelper->initializeArguments();
        self::assertAttributeNotEmpty('argumentDefinitions', $this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        self::assertFalse($this->viewHelper->isOutputEscapingEnabled());
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects(self::never())->method('renderChildren');
        $this->viewHelper->setArguments(
            ['value' => 'Some string', 'keepQuotes' => false, 'encoding' => 'UTF-8', 'doubleEncode' => false]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Some string', $actualResult);
    }

    /**
     * test
     */
    public function renderUsesChildNodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects(self::atLeastOnce())->method('renderChildren')->willReturn('Some string');
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Some string', $actualResult);
    }

    public function dataProvider()
    {
        return [
            // render does not modify string without special characters
            [
                'value' => 'This is a sample text without special characters.',
                'options' => [],
                'expectedResult' => 'This is a sample text without special characters.'
            ],
            // render decodes simple string
            [
                'value' => 'Some special characters: &©"\'',
                'options' => [],
                'expectedResult' => 'Some special characters: &amp;©&quot;&#039;'
            ],
            // render respects "keepQuotes" argument
            [
                'value' => 'Some special characters: &©"',
                'options' => [
                    'keepQuotes' => true,
                ],
                'expectedResult' => 'Some special characters: &amp;©"'
            ],
            // render respects "encoding" argument
            [
                'value' => utf8_decode('Some special characters: &"\''),
                'options' => [
                    'encoding' => 'ISO-8859-1',
                ],
                'expectedResult' => 'Some special characters: &amp;&quot;&#039;'
            ],
            // render converts already converted entities by default
            [
                'value' => 'already &quot;encoded&quot;',
                'options' => [],
                'expectedResult' => 'already &amp;quot;encoded&amp;quot;'
            ],
            // render does not convert already converted entities if "doubleEncode" is FALSE
            [
                'value' => 'already &quot;encoded&quot;',
                'options' => [
                    'doubleEncode' => false,
                ],
                'expectedResult' => 'already &quot;encoded&quot;'
            ],
            // render returns unmodified source if it is a float
            [
                'value' => 123.45,
                'options' => [],
                'expectedResult' => 123.45
            ],
            // render returns unmodified source if it is an integer
            [
                'value' => 12345,
                'options' => [],
                'expectedResult' => 12345
            ],
            // render returns unmodified source if it is a boolean
            [
                'value' => true,
                'options' => [],
                'expectedResult' => true
            ],
        ];
    }

    /**
     * test
     *
     * @dataProvider dataProvider
     */
    public function renderTests($value, array $options, $expectedResult)
    {
        $options['value'] = $value;
        $this->viewHelper->setArguments($options);
        self::assertSame($expectedResult, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function renderTestsWithRenderChildrenFallback($value, array $options, $expectedResult)
    {
        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn($value);
        $options['value'] = null;
        $options['keepQuotes'] = (boolean)(isset($options['keepQuotes']) && $options['keepQuotes'] ? $options['keepQuotes'] : false);
        $options['encoding'] = 'UTF-8';
        $options['doubleEncode'] = (boolean)(isset($options['doubleEncode']) ? $options['doubleEncode'] : true);
        $this->viewHelper->setArguments($options);
        self::assertSame($expectedResult, $this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * __test
     *
     * @dataProvider dataProvider
     */
    public function compileTests($value, array $options, $expectedResult)
    {
        /** @var ViewHelperNode&MockObject $mockSyntaxTreeNode */
        $mockSyntaxTreeNode = $this->getMockBuilder(ViewHelperNode::class)->disableOriginalConstructor()->getMock();

        /** @var TemplateCompiler&MockObject $mockTemplateCompiler */
        $mockTemplateCompiler = $this->getMockBuilder(TemplateCompiler::class)->disableOriginalConstructor()->getMock();
        $mockTemplateCompiler->expects(self::once())->method('variableName')->with('value')->willReturn('$value123');

        $arguments = [
            'value' => $value,
            'keepQuotes' => (boolean)(isset($options['keepQuotes']) && $options['keepQuotes'] ? $options['keepQuotes'] : false),
            'encoding' => 'UTF-8',
            'doubleEncode' => (boolean)(isset($options['doubleEncode']) ? $options['doubleEncode'] : true),
        ];
        $arguments = array_merge($arguments, $options);
        $initializationPhpCode = '$arguments = ' . var_export($arguments, true) . ';' . chr(10);
        $compiledPhpCode = $this->viewHelper->compile('$arguments', 'NULL', $initializationPhpCode, $mockSyntaxTreeNode, $mockTemplateCompiler);
        $result = null;
        eval($initializationPhpCode . '$result = ' . $compiledPhpCode . ';');
        self::assertSame($expectedResult, $result);
    }

    /**
     * __test
     *
     * @dataProvider dataProvider
     */
    public function compileTestsWithRenderChildrenFallback($value, array $options, $expectedResult)
    {
        /** @var ViewHelperNode&MockObject $mockSyntaxTreeNode */
        $mockSyntaxTreeNode = $this->getMockBuilder(ViewHelperNode::class)->disableOriginalConstructor()->getMock();

        /** @var TemplateCompiler&MockObject $mockTemplateCompiler */
        $mockTemplateCompiler = $this->getMockBuilder(TemplateCompiler::class)->disableOriginalConstructor()->getMock();
        $mockTemplateCompiler->expects(self::once())->method('variableName')->with('value')->willReturn('$value123');

        $renderChildrenClosureName = uniqid('renderChildren');
        $arguments = [
            'value' => null,
            'keepQuotes' => (boolean)isset($options['keepQuotes']) && $options['keepQuotes'],
            'encoding' => isset($options['keepQuotes']) ? $options['keepQuotes'] : 'UTF-8',
            'doubleEncode' => (boolean)isset($options['doubleEncode']) && $options['doubleEncode'],
        ];
        $arguments = array_merge($arguments, $options);
        $initializationPhpCode = 'function ' . $renderChildrenClosureName . '() { return ' . var_export($value, true) . '; }; ' . chr(10) . '$arguments = ' . var_export($arguments, true) . ';' . chr(10);
        $compiledPhpCode = $this->viewHelper->compile('$arguments', $renderChildrenClosureName, $initializationPhpCode, $mockSyntaxTreeNode, $mockTemplateCompiler);
        $result = null;
        eval($initializationPhpCode . '$result = ' . $compiledPhpCode . ';');
        self::assertSame($expectedResult, $result);
    }

    /**
     * __test
     */
    public function renderConvertsObjectsToStrings()
    {
        $user = new UserWithToString('Xaver <b>Cross-Site</b>');
        $expectedResult = 'Xaver &lt;b&gt;Cross-Site&lt;/b&gt;';
        $this->viewHelper->setArguments(['value' => $user]);
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * __test
     */
    public function renderDoesNotModifySourceIfItIsAnObjectThatCantBeConvertedToAString()
    {
        $user = new UserWithoutToString('Xaver <b>Cross-Site</b>');
        $this->viewHelper->setArguments(['value' => $user]);
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertSame($user, $actualResult);
    }

    /**
     * __test
     */
    public function compileConvertsObjectsToStrings()
    {
        /** @var AbstractNode&MockObject $mockSyntaxTreeNode */
        $mockSyntaxTreeNode = $this->getMockBuilder(AbstractNode::class)->disableOriginalConstructor()->getMock();

        /** @var TemplateCompiler&MockObject $mockTemplateCompiler */
        $mockTemplateCompiler = $this->getMockBuilder(TemplateCompiler::class)->disableOriginalConstructor()->getMock();
        $mockTemplateCompiler->expects(self::once())->method('variableName')->with('value')->willReturn('$value123');

        $initializationPhpCode = '$arguments = array("value" => new \TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\Fixtures\UserWithToString("Xaver <b>Cross-Site</b>"), "keepQuotes" => FALSE, "encoding" => "UTF-8", "doubleEncode" => TRUE);' . chr(10);
        $compiledPhpCode = $this->viewHelper->compile('$arguments', 'NULL', $initializationPhpCode, $mockSyntaxTreeNode, $mockTemplateCompiler);
        $result = null;
        eval($initializationPhpCode . '$result = ' . $compiledPhpCode . ';');
        self::assertSame('Xaver &lt;b&gt;Cross-Site&lt;/b&gt;', $result);
    }

    /**
     * @test
     */
    public function compileDoesNotModifySourceIfItIsAnObjectThatCantBeConvertedToAString()
    {
        /** @var ViewHelperNode&MockObject $mockSyntaxTreeNode */
        $mockSyntaxTreeNode = $this->getMockBuilder(ViewHelperNode::class)->disableOriginalConstructor()->getMock();

        /** @var TemplateCompiler&MockObject $mockTemplateCompiler */
        $mockTemplateCompiler = $this->getMockBuilder(TemplateCompiler::class)->disableOriginalConstructor()->getMock();
        $mockTemplateCompiler->expects(self::once())->method('variableName')->with('value')->willReturn('$value123');

        $initializationPhpCode = '$arguments = array("value" => new \TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\Fixtures\UserWithoutToString("Xaver <b>Cross-Site</b>"), "keepQuotes" => FALSE, "encoding" => "UTF-8", "doubleEncode" => TRUE);' . chr(10);
        $compiledPhpCode = $this->viewHelper->compile('$arguments', 'NULL', $initializationPhpCode, $mockSyntaxTreeNode, $mockTemplateCompiler);
        $result = null;
        eval($initializationPhpCode . '$result = ' . $compiledPhpCode . ';');
        self::assertEquals(new UserWithoutToString('Xaver <b>Cross-Site</b>'), $result);
    }
}
