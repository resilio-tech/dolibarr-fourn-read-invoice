<?php

dol_include_once('/fournreadinvoice/class/fournreadfile.class.php');
dol_include_once('/fournreadinvoice/lib/fournreadinvoice.lib.php');

class ActionsFournReadInvoice
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     * Execute action
     *
     * @param	array			$parameters		Array of parameters
     * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param	string			$action      	'add', 'update', 'view'
     * @return	int         					<0 if KO,
     *                           				=0 if OK but we want to process standard actions too,
     *                            				>0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action)
    {
        global $db, $langs, $conf, $user;

        $this->resprints = '';
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0;

        if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
            // Do what you want here...
        }

        if (!$error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0;
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    /**
     * @param   [type]  $parameters  [$parameters description]
     * @param   [type]  $data        [$data description]
     * @param   [type]  $operation   [$operation description]
     *
     * @return  [type]               [return description]
     */
    public function addmoduletoeamailcollectorjoinpiece($parameters, &$data, &$operation)
    {
        include_once DOL_DOCUMENT_ROOT.'/emailcollector/lib/emailcollector.lib.php';

        if ($operation['actionparam'] != "fournreadinvoice") {
            return 0;
        }

        foreach ($data as $filenameTmp => $content) {
			$result = Fournreadfile::uploadFile($filenameTmp, $content);
			if (!is_array($result)) {
				dol_syslog("fournreadinvoice create : " . $filenameTmp . " pushed to import queue success !");
				$this->db->commit();
			}
        }

        return 0;
    }
}
