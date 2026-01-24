<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       class/FileUploadValidator.class.php
 * \ingroup    fournreadinvoice
 * \brief      Class for validating and sanitizing file uploads (standalone, no Dolibarr dependency)
 */

/**
 * Class FileUploadValidator
 *
 * Validates and sanitizes file names for upload
 * This class has no dependency on Dolibarr and can be unit tested independently
 */
class FileUploadValidator
{
	/**
	 * @var array Error messages
	 */
	public $errors = array();

	/**
	 * @var array Accepted file extensions
	 */
	protected $acceptedExtensions = array('pdf', 'jpg', 'jpeg');

	/**
	 * Constructor
	 *
	 * @param array|null $acceptedExtensions Optional custom list of accepted extensions
	 */
	public function __construct(?array $acceptedExtensions = null)
	{
		if ($acceptedExtensions !== null) {
			$this->acceptedExtensions = array_map('strtolower', $acceptedExtensions);
		}
	}

	/**
	 * Validate a filename for upload
	 *
	 * @param string $filename The filename to validate
	 * @return bool True if valid, false otherwise
	 */
	public function validate(string $filename): bool
	{
		$this->errors = array();

		if (empty($filename)) {
			$this->errors[] = 'FilenameEmpty';
			return false;
		}

		$extension = $this->getExtension($filename);

		if (empty($extension)) {
			$this->errors[] = 'ExtensionMissing';
			return false;
		}

		if (!$this->isExtensionAllowed($extension)) {
			$this->errors[] = 'ExtensionNotAllowed';
			return false;
		}

		return true;
	}

	/**
	 * Sanitize a filename for safe storage
	 *
	 * @param string $filename The original filename
	 * @return string Sanitized filename
	 */
	public function sanitize(string $filename): string
	{
		if (empty($filename)) {
			return '';
		}

		$extension = $this->getExtension($filename);
		$basename = $this->getBasename($filename);

		// Replace non-word characters with hyphens
		$sanitized = preg_replace('/[^\pL\d]+/u', '-', $basename);

		// Transliterate to ASCII
		if (function_exists('iconv')) {
			$sanitized = @iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $sanitized);
		}

		// Remove any remaining non-alphanumeric characters except hyphens
		$sanitized = preg_replace('/[^-\w]+/', '', $sanitized);

		// Trim hyphens from start and end
		$sanitized = trim($sanitized, '-');

		// Replace multiple consecutive hyphens with single hyphen
		$sanitized = preg_replace('/-+/', '-', $sanitized);

		// Convert to lowercase
		$sanitized = strtolower($sanitized);

		// If nothing remains, use a default name
		if (empty($sanitized)) {
			$sanitized = 'file';
		}

		// Reconstruct with extension
		if (!empty($extension)) {
			$sanitized .= '.' . strtolower($extension);
		}

		return $sanitized;
	}

	/**
	 * Get the file extension from a filename
	 *
	 * @param string $filename The filename
	 * @return string The extension (lowercase, without dot)
	 */
	public function getExtension(string $filename): string
	{
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		return strtolower($extension);
	}

	/**
	 * Get the basename (filename without extension)
	 *
	 * @param string $filename The filename
	 * @return string The basename
	 */
	public function getBasename(string $filename): string
	{
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if (empty($extension)) {
			return $filename;
		}
		return substr($filename, 0, -(strlen($extension) + 1));
	}

	/**
	 * Check if an extension is allowed
	 *
	 * @param string $extension The extension to check
	 * @return bool True if allowed
	 */
	public function isExtensionAllowed(string $extension): bool
	{
		return in_array(strtolower($extension), $this->acceptedExtensions);
	}

	/**
	 * Get the list of accepted extensions
	 *
	 * @return array Array of accepted extensions
	 */
	public function getAcceptedExtensions(): array
	{
		return $this->acceptedExtensions;
	}

	/**
	 * Set accepted extensions
	 *
	 * @param array $extensions Array of extensions
	 * @return void
	 */
	public function setAcceptedExtensions(array $extensions): void
	{
		$this->acceptedExtensions = array_map('strtolower', $extensions);
	}

	/**
	 * Generate a unique filename with timestamp
	 *
	 * @param string $originalFilename The original filename
	 * @return string Unique filename with timestamp
	 */
	public function generateUniqueFilename(string $originalFilename): string
	{
		$sanitized = $this->sanitize($originalFilename);
		$extension = $this->getExtension($sanitized);
		$basename = $this->getBasename($sanitized);

		$timestamp = date('Ymd-His');
		$uniqueId = substr(uniqid(), -4);

		if (!empty($extension)) {
			return $basename . '-' . $timestamp . '-' . $uniqueId . '.' . $extension;
		}

		return $basename . '-' . $timestamp . '-' . $uniqueId;
	}

	/**
	 * Check if validation passed without errors
	 *
	 * @return bool True if no errors
	 */
	public function isValid(): bool
	{
		return empty($this->errors);
	}

	/**
	 * Get all error messages
	 *
	 * @return array Array of error message keys
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
