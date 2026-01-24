<?php
/**
 * Standalone unit tests for FileUploadValidator
 * No Dolibarr dependency required
 *
 * Run with: phpunit htdocs/custom/fournreadinvoice/test/phpunit/unit/FileUploadValidatorTest.php
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__).'/../../../class/FileUploadValidator.class.php';

class FileUploadValidatorTest extends TestCase
{
	/**
	 * @var FileUploadValidator
	 */
	private $validator;

	protected function setUp(): void
	{
		$this->validator = new FileUploadValidator();
	}

	// ========================================
	// Tests for validate()
	// ========================================

	public function testValidateValidPdf()
	{
		$result = $this->validator->validate('invoice.pdf');

		$this->assertTrue($result);
		$this->assertTrue($this->validator->isValid());
	}

	public function testValidateValidJpg()
	{
		$result = $this->validator->validate('photo.jpg');

		$this->assertTrue($result);
	}

	public function testValidateValidJpeg()
	{
		$result = $this->validator->validate('photo.jpeg');

		$this->assertTrue($result);
	}

	public function testValidateEmptyFilename()
	{
		$result = $this->validator->validate('');

		$this->assertFalse($result);
		$this->assertContains('FilenameEmpty', $this->validator->getErrors());
	}

	public function testValidateNoExtension()
	{
		$result = $this->validator->validate('filename');

		$this->assertFalse($result);
		$this->assertContains('ExtensionMissing', $this->validator->getErrors());
	}

	public function testValidateNotAllowedExtension()
	{
		$result = $this->validator->validate('file.exe');

		$this->assertFalse($result);
		$this->assertContains('ExtensionNotAllowed', $this->validator->getErrors());
	}

	public function testValidateCaseInsensitiveExtension()
	{
		$result = $this->validator->validate('invoice.PDF');

		$this->assertTrue($result);
	}

	// ========================================
	// Tests for sanitize()
	// ========================================

	public function testSanitizeSimpleFilename()
	{
		$result = $this->validator->sanitize('invoice.pdf');

		$this->assertEquals('invoice.pdf', $result);
	}

	public function testSanitizeFilenameWithSpaces()
	{
		$result = $this->validator->sanitize('my invoice file.pdf');

		$this->assertEquals('my-invoice-file.pdf', $result);
	}

	public function testSanitizeFilenameWithSpecialChars()
	{
		$result = $this->validator->sanitize('facture_client_#123.pdf');

		$this->assertEquals('facture-client-123.pdf', $result);
	}

	public function testSanitizeFilenameWithAccents()
	{
		$result = $this->validator->sanitize('facture-été-2024.pdf');

		// After transliteration, accented characters may vary
		$this->assertMatchesRegularExpression('/^facture-.*-2024\.pdf$/', $result);
	}

	public function testSanitizeConvertsToLowercase()
	{
		$result = $this->validator->sanitize('MyInvoice.PDF');

		$this->assertEquals('myinvoice.pdf', $result);
	}

	public function testSanitizeRemovesMultipleHyphens()
	{
		$result = $this->validator->sanitize('file---name.pdf');

		$this->assertEquals('file-name.pdf', $result);
	}

	public function testSanitizeTrimsHyphens()
	{
		$result = $this->validator->sanitize('---filename---.pdf');

		$this->assertEquals('filename.pdf', $result);
	}

	public function testSanitizeEmptyBasename()
	{
		$result = $this->validator->sanitize('###.pdf');

		$this->assertEquals('file.pdf', $result);
	}

	public function testSanitizeEmptyFilename()
	{
		$result = $this->validator->sanitize('');

		$this->assertEquals('', $result);
	}

	// ========================================
	// Tests for getExtension()
	// ========================================

	public function testGetExtensionSimple()
	{
		$result = $this->validator->getExtension('file.pdf');

		$this->assertEquals('pdf', $result);
	}

	public function testGetExtensionUppercase()
	{
		$result = $this->validator->getExtension('file.PDF');

		$this->assertEquals('pdf', $result);
	}

	public function testGetExtensionWithPath()
	{
		$result = $this->validator->getExtension('/path/to/file.pdf');

		$this->assertEquals('pdf', $result);
	}

	public function testGetExtensionNoExtension()
	{
		$result = $this->validator->getExtension('filename');

		$this->assertEquals('', $result);
	}

	public function testGetExtensionMultipleDots()
	{
		$result = $this->validator->getExtension('file.name.ext.pdf');

		$this->assertEquals('pdf', $result);
	}

	// ========================================
	// Tests for getBasename()
	// ========================================

	public function testGetBasenameSimple()
	{
		$result = $this->validator->getBasename('file.pdf');

		$this->assertEquals('file', $result);
	}

	public function testGetBasenameNoExtension()
	{
		$result = $this->validator->getBasename('filename');

		$this->assertEquals('filename', $result);
	}

	public function testGetBasenameMultipleDots()
	{
		$result = $this->validator->getBasename('file.name.ext.pdf');

		$this->assertEquals('file.name.ext', $result);
	}

	// ========================================
	// Tests for isExtensionAllowed()
	// ========================================

	public function testIsExtensionAllowedPdf()
	{
		$this->assertTrue($this->validator->isExtensionAllowed('pdf'));
	}

	public function testIsExtensionAllowedJpg()
	{
		$this->assertTrue($this->validator->isExtensionAllowed('jpg'));
	}

	public function testIsExtensionAllowedJpeg()
	{
		$this->assertTrue($this->validator->isExtensionAllowed('jpeg'));
	}

	public function testIsExtensionAllowedCaseInsensitive()
	{
		$this->assertTrue($this->validator->isExtensionAllowed('PDF'));
		$this->assertTrue($this->validator->isExtensionAllowed('JPG'));
	}

	public function testIsExtensionNotAllowed()
	{
		$this->assertFalse($this->validator->isExtensionAllowed('exe'));
		$this->assertFalse($this->validator->isExtensionAllowed('php'));
		$this->assertFalse($this->validator->isExtensionAllowed('txt'));
	}

	// ========================================
	// Tests for getAcceptedExtensions() and setAcceptedExtensions()
	// ========================================

	public function testGetAcceptedExtensions()
	{
		$extensions = $this->validator->getAcceptedExtensions();

		$this->assertContains('pdf', $extensions);
		$this->assertContains('jpg', $extensions);
		$this->assertContains('jpeg', $extensions);
	}

	public function testSetAcceptedExtensions()
	{
		$this->validator->setAcceptedExtensions(array('png', 'gif'));

		$this->assertTrue($this->validator->isExtensionAllowed('png'));
		$this->assertTrue($this->validator->isExtensionAllowed('gif'));
		$this->assertFalse($this->validator->isExtensionAllowed('pdf'));
	}

	public function testConstructorWithCustomExtensions()
	{
		$validator = new FileUploadValidator(array('PNG', 'GIF'));

		$this->assertTrue($validator->isExtensionAllowed('png'));
		$this->assertTrue($validator->isExtensionAllowed('gif'));
		$this->assertFalse($validator->isExtensionAllowed('pdf'));
	}

	// ========================================
	// Tests for generateUniqueFilename()
	// ========================================

	public function testGenerateUniqueFilenameFormat()
	{
		$result = $this->validator->generateUniqueFilename('invoice.pdf');

		// Format: basename-YYYYMMDD-HHMMSS-xxxx.ext
		$this->assertMatchesRegularExpression('/^invoice-\d{8}-\d{6}-[a-f0-9]{4}\.pdf$/', $result);
	}

	public function testGenerateUniqueFilenameSanitizes()
	{
		$result = $this->validator->generateUniqueFilename('My Invoice #123.pdf');

		$this->assertMatchesRegularExpression('/^my-invoice-123-\d{8}-\d{6}-[a-f0-9]{4}\.pdf$/', $result);
	}

	public function testGenerateUniqueFilenameUniqueness()
	{
		$result1 = $this->validator->generateUniqueFilename('file.pdf');
		usleep(1000); // Small delay
		$result2 = $this->validator->generateUniqueFilename('file.pdf');

		$this->assertNotEquals($result1, $result2);
	}
}
