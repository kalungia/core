<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Tables\View\GridView;
use Gibbon\Domain\DataSet;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php', ['search' => $search])
        ->add(__('Edit Template'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$gibbon->session->get('absoluteURL')."/index.php?q=/modules/Reports/templates_manage.php&search=$search'>".__('Back to Search Results').'</a>';
        echo '</div>';
    }

    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

    if (empty($gibbonReportTemplateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $templateGateway->getByID($gibbonReportTemplateID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('templatesManage', $gibbon->session->get('absoluteURL').'/modules/Reports/templates_manage_editProcess.php?search='.$search);

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);

    $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addTextField('context')->readonly();

    $form->addRow()->addHeading(__('Document Setup'));

    $orientations = ['P' => __('Portrait'), 'L' => __('Landscape')];
    $row = $form->addRow();
        $row->addLabel('orientation', __('Orientation'));
        $row->addSelect('orientation')->fromArray($orientations)->required();

    $pageSizes = ['A4' => __('A4'), 'letter' => __('US Letter')];
    $row = $form->addRow();
        $row->addLabel('pageSize', __('Page Size'));
        $row->addSelect('pageSize')->fromArray($pageSizes)->required();

    $row = $form->addRow();
        $row->addLabel('margins', __('Margins'));
        $col = $row->addColumn()->addClass('items-center');
        $col->addContent('<div class="flex-1 pr-1">X</div>');
        $col->addNumber('marginX')->decimalPlaces(2)->required();
        $col->addContent('<div class="flex-1 pr-1 pl-2">Y</div>');
        $col->addNumber('marginY')->decimalPlaces(2)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    // QUERY
    $criteria = $templateSectionGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'ASC')
        ->fromPOST();

    // DATA TABLE
    $table = $container->get(DataTable::class);
    $table->addMetaData('blankSlate', __('There are no sections here yet.'));

    $draggableAJAX = $gibbon->session->get('absoluteURL').'/modules/Reports/templates_manage_editOrderAjax.php';
    $table->addDraggableColumn('gibbonReportTemplateSectionID', $draggableAJAX, [
        'gibbonReportTemplateID' => $gibbonReportTemplateID,
    ]);

    $table->addColumn('name', __('Name'));

    $table->addActionColumn()
        ->addParam('gibbonReportTemplateID', $gibbonReportTemplateID)
        ->addParam('gibbonReportTemplateSectionID')
        ->format(function ($template, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/templates_manage_section_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Reports/templates_manage_section_delete.php');
        });
        

    // HEADERS
    $headerSections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Header');
    $headerTable = clone $table;
    $headerTable->setTitle(__('Header'));
    $headerTable->setID('headerTable');

    // BODY
    $bodySections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Body');
    $bodyTable = clone $table;
    $bodyTable->setTitle(__('Body'));
    $bodyTable->setID('bodyTable');

    // FOOTER
    $footerSections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Footer');
    $footerTable = clone $table;
    $footerTable->setTitle(__('Footer'));
    $footerTable->setID('footerTable');

    // PROTOTYPE
    $prototypeSections = $templateSectionGateway->selectPrototypeSections()->fetchAll();

    echo $page->fetchFromTemplate('ui/templateBuilder.twig.html', [
        'gibbonReportTemplateID' => $gibbonReportTemplateID,
        'template' => $values,
        'form'     => $form->getOutput(),
        'headers'  => $headerTable->render($headerSections),
        'body'     => $bodyTable->render($bodySections),
        'footers'  => $footerTable->render($footerSections),
        'sections' => $prototypeSections,
    ]);
}