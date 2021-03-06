<?php
$relPath="./../../pinc/";
include_once($relPath.'base.inc');
include_once($relPath.'slim_header.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'misc.inc'); // get_enumerated_param()
include_once($relPath.'ProofreadingToolbox.inc');

$round_id = get_enumerated_param($_GET, 'round_id', null, array_keys($Round_for_round_id_));
$round = get_Round_for_round_id($round_id);

$header_args = array(
    "body_attributes" => 'onLoad="top.cRef = top.markRef = document.markform;" style="background-color: #CDC0B0; padding: 0; margin: 0;"',
);
slim_header(_("Control Frame"), $header_args);

$toolbox = new ProofreadingToolbox();
$toolbox->output($round);
