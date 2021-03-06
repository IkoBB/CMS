<?php
/**
 *
 * This file is part of IkoBB Forum and belongs to the module <CMS>.
 *
 * @copyright (c) 2017 IkoBB <https://www.ikobb.de>
 * @license       GNU General Public License, version 3 (GPL-3.0)
 *
 * For full copyright and license information, please see
 * the LICENSE file.
 *
 */

namespace iko\cms;

use iko\{
	Core, PDO, Exception
};

class page
{
	/**
	 * Table name with {prefix} for the custom pages
	 */
	const table = "{prefix}pages";
	/**
	 * Column name for the colum 'id' for custom pages
	 */
	const column_id = "page_id";

	function __construct ()
	{

	}

	/**
	 * Checks if a custom page exists
	 *
	 * @param $site_id
	 *
	 * @return bool
	 */
	public static function exists ($site_id): bool
	{
		if (is_numeric($site_id) && $site_id != 0 && $site_id != NULL) {
			$statement = Core::PDO()->prepare("SELECT " . self::column_id . " FROM " . self::table . " WHERE " . self::column_id . " = :id");
			$statement->bindParam(':id', $site_id);
			$statement->execute();
			if ($statement->rowCount() > 0) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Loads a custom page
	 *
	 * @param $site_id
	 *
	 * @throws \iko\Exception
	 */
	private function load_content (int $site_id = 0)
	{

		$template = template::get_instance();
		if ($site_id != 0) {
			try {
				$statement = Core::PDO()->prepare("SELECT * FROM " . self::table . " WHERE " . self::column_id . " = :id");
				$statement->bindParam(':id', $site_id);
				$statement->execute();
				$site = $statement->fetch(PDO::FETCH_ASSOC);
			}
			catch (\PDOException $exception) {
				throw new Exception("Error #1234: " . $exception);
			}

			if ($site !== FALSE) {
				//$site['page_sidebar'] == "1" ? template::add_sidebar() : template::no_sidebar();
				$template->sub_title = $site['page_title'];
				$parser = new parser();
				$template->content = entity::return_entity("cms.page",
					array ("page_content" => $parser->parse($site["page_content"])));
				template::add_breadcrumb($site['page_title'], "?module=cms&id=" . $site_id);
			}
			else {
				$this->load_content();
			}
		}
		else {
			$template->sub_title = 'Page not found';
			$template->content = entity::return_entity("cms.error404");
			template::add_breadcrumb("Error 404", "#");
		}

	}

	/**
	 * Page output function
	 * This function will handle the output for the module cms
	 *
	 * $event_name and $var are not used in this function. They are needed for the Event handler
	 *
	 * @param string $event_name
	 * @param array  $args
	 * @param array  $var
	 */
	public function init_page ($event_name, $args, $var = NULL)
	{
		$template = template::get_instance();
		if (array_key_exists('id', $args) && self::exists($args['id']) == TRUE) {
			$this->load_content((int)$args['id']);
		}
		else {
			$this->load_content();
		}
		echo $template;
	}
}