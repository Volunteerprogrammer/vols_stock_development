<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockEventSummaryForm extends \fw\view\form\StdCRUDForm {
    protected $trace      = false;
    protected $formname   = "stockeventsummaryform";
    protected $objname    = "Stock Event History";
    protected $fields     = [];
    protected $names      = [];
    protected $parents    = [];

    private const PAGEMAP = [
        'stocktake'  => 411,
        'delivery'   => 412,
        'transfer'   => 413,
        'adjustment' => 414,
    ];

    private const TYPE_LABEL = [
        'stocktake'  => 'Stocktake',
        'delivery'   => 'Delivery',
        'transfer'   => 'Transfer',
        'adjustment' => 'Adjustment',
        'issue'      => 'Issue',
    ];

    public function __construct(protected FormComponent $component) {
        $this->singlerecord  = false;
        $this->actionbuttons = [];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = [
            "id"             => "",
            "event"          => "",
            "date_closed"    => "",
            "total_weight"   => "",
            "location1_id"   => "",
            "location2_id"   => "",
            "supplier_id"    => "",
            "location1_name" => "",
            "location2_name" => "",
            "supplier_name"  => "",
        ];
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["date_closed"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $locations   = $this->parents['locations']   ?? [];
        $location_id = $this->parents['location_id'] ?? '';
        $from_val    = (!empty($this->parents['from'])) ? $this->parents['from'] : date('Y-m-d', strtotime('-30 days'));
        $to_val      = (!empty($this->parents['to']))   ? $this->parents['to']   : date('Y-m-d');
        $queried     = !empty($this->parents['from']) && !empty($this->parents['to']);

        $this->component->setheadingoverride("Stock Reports");
        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels">Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance">Stocktake Variance</option>';
        $rtype .= '<option value="usagereport">Usage Report</option>';
        $rtype .= '<option value="deliveriesreport">Deliveries Report</option>';
        $rtype .= '<option value="belowminimumreport">Low Stock</option>';
        $rtype .= '<option value="eventhistory" selected>Event History</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';

        $formfields  = '<div class="vols-stockreport-layout"><div class="vols-stockreport-controls">';

        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="location_id">Location:</label>';
        $formfields .= '<select id="location_id" name="location_id" class="vols-stockreport-locselect" onchange="this.form.submit()">';
        $formfields .= '<option value="">All locations</option>';
        foreach ($locations as $loc) {
            $sel = ((string)$loc['id'] === (string)$location_id) ? ' selected' : '';
            $formfields .= '<option value="' . (int)$loc['id'] . '"' . $sel . '>'
                         . htmlspecialchars($loc['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-usagereport-filter">';
        $formfields .= '<label class="vols-usagereport-label">From</label>';
        $formfields .= '<input type="date" name="from" class="vols-usagereport-dateinput" value="' . htmlspecialchars($from_val) . '">';
        $formfields .= '<label class="vols-usagereport-label">To</label>';
        $formfields .= '<input type="date" name="to" class="vols-usagereport-dateinput" value="' . htmlspecialchars($to_val) . '">';
        $formfields .= '<button type="submit" class="vols-usagereport-genbtn">Generate Report</button>';
        $formfields .= '</div>';

        $formfields .= '</div><div class="vols-stockreport-scroll">';

        if ($queried) {
            $from_disp = date('d-m-Y', strtotime($this->parents['from']));
            $to_disp   = date('d-m-Y', strtotime($this->parents['to']));

            $jsrows = [];
            foreach ($this->alldata as $row) {
                $dt = $row['date_closed'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $row['date_closed']) : false;
                $jsrows[] = json_encode([
                    'date'     => $dt ? $dt->format('d-m-Y H:i') : '',
                    'type'     => self::TYPE_LABEL[$row['event']] ?? ucfirst($row['event']),
                    'location' => $row['location1_name'] ?? '',
                    'to_loc'   => $row['location2_name'] ?? '',
                    'supplier' => $row['supplier_name']  ?? '',
                ]);
            }
            $formfields .= '<script>var summaryReportData=[' . implode(',', $jsrows) . '];</script>';
            $formfields .= '<script>var summaryReportFrom="' . htmlspecialchars($from_disp) . '";'
                         . 'var summaryReportTo="'   . htmlspecialchars($to_disp)   . '";</script>';

            $formfields .= '<div class="vols-usagereport-summary">';
            $formfields .= 'Events closed from <strong>' . $from_disp . '</strong> to <strong>' . $to_disp . '</strong>';
            if (empty($this->alldata)) {
                $formfields .= ' &mdash; no events found.';
            } else {
                $formfields .= ' &mdash; ' . count($this->alldata) . ' event(s).';
                $formfields .= '<button type="button" class="vols-usagereport-csvbtn" onclick="downloadSummaryCSV()">&#8681; Export CSV</button>';
            }
            $formfields .= '</div>';

            if (!empty($this->alldata)) {
                $formfields .= '<div class="vols-eventsummary-wrap">';
                $formfields .= '<div class="vols-eventsummary-colheadings">';
                $formfields .= '<div class="vols-eventsummary-col-date">Date</div>';
                $formfields .= '<div class="vols-eventsummary-col-type">Type</div>';
                $formfields .= '<div class="vols-eventsummary-col-loc">Location</div>';
                $formfields .= '<div class="vols-eventsummary-col-loc2">To Location</div>';
                $formfields .= '<div class="vols-eventsummary-col-sup">Supplier</div>';
                $formfields .= '<div class="vols-eventsummary-col-action"></div>';
                $formfields .= '</div>';

                $n = 0;
                foreach ($this->alldata as $row) {
                    $event_type = $row['event'] ?? '';
                    $page       = self::PAGEMAP[$event_type]  ?? 0;
                    $label      = self::TYPE_LABEL[$event_type] ?? ucfirst($event_type);
                    $loc1       = (int)($row['location1_id'] ?? 0);
                    $loc2       = (int)($row['location2_id'] ?? 0);
                    $supp       = (int)($row['supplier_id']  ?? 0);
                    $btnclick   = $page
                        ? 'summaryNavigate(' . $page . ',' . (int)$row['id'] . ',' . $loc1 . ',' . $loc2 . ',' . $supp . ')'
                        : '';

                    $dt      = $row['date_closed'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $row['date_closed']) : false;
                    $datestr = $dt ? $dt->format('D d-m-Y H:i') : '—';
                    $stripe  = (($n++ % 2) === 0) ? 'vols-row-odd' : 'vols-row-even';

                    $formfields .= '<div class="vols-eventsummary-row ' . $stripe . '">';
                    $formfields .= '<div class="vols-eventsummary-col-date">' . $datestr . '</div>';
                    $formfields .= '<div class="vols-eventsummary-col-type vols-eventsummary-type-' . htmlspecialchars($event_type) . '">' . $label . '</div>';
                    $formfields .= '<div class="vols-eventsummary-col-loc">'  . htmlspecialchars($row['location1_name'] ?? '') . '</div>';
                    $formfields .= '<div class="vols-eventsummary-col-loc2">' . htmlspecialchars($row['location2_name'] ?? '') . '</div>';
                    $formfields .= '<div class="vols-eventsummary-col-sup">'  . htmlspecialchars($row['supplier_name']  ?? '') . '</div>';
                    $formfields .= '<div class="vols-eventsummary-col-action">';
                    if ($btnclick) {
                        $formfields .= '<button type="button" class="vols-button vols-button-small" onclick="' . $btnclick . '">Go to</button>';
                    }
                    $formfields .= '</div>';
                    $formfields .= '</div>';
                }

                $formfields .= '</div>';
            }
        }

        $formfields .= '</div></div>';
        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="eventhistory">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return <<<'JS'
function formhaserrors() { return 0; }
function displayselectedrecord() {}

function summaryNavigate(page, eventId, loc1Id, loc2Id, suppId) {
    var $form = jQuery('#menuactionform');
    $form.find('.resume-param').remove();
    $form.find('input[name="p"]').val(page);
    jQuery('<input>').attr({ type: 'hidden', name: 'resume_event_id',     class: 'resume-param', value: eventId }).appendTo($form);
    jQuery('<input>').attr({ type: 'hidden', name: 'resume_page',         class: 'resume-param', value: page    }).appendTo($form);
    jQuery('<input>').attr({ type: 'hidden', name: 'resume_location1_id', class: 'resume-param', value: loc1Id  }).appendTo($form);
    if (loc2Id) jQuery('<input>').attr({ type: 'hidden', name: 'resume_location2_id', class: 'resume-param', value: loc2Id }).appendTo($form);
    if (suppId) jQuery('<input>').attr({ type: 'hidden', name: 'resume_supplier_id',  class: 'resume-param', value: suppId }).appendTo($form);
    $form.submit();
}

function downloadSummaryCSV() {
    var rows = [['Date','Type','Location','To Location','Supplier']];
    for (var i = 0; i < summaryReportData.length; i++) {
        var r = summaryReportData[i];
        rows.push([r.date, r.type, r.location, r.to_loc, r.supplier]);
    }
    var csv = rows.map(function(row) {
        return row.map(function(v) {
            var s = String(v);
            return (s.indexOf(',') !== -1 || s.indexOf('"') !== -1)
                ? '"' + s.replace(/"/g, '""') + '"' : s;
        }).join(',');
    }).join('\r\n');
    var blob = new Blob(['﻿' + csv], {type: 'text/csv;charset=utf-8'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    var d = new Date(), pad = function(n){return String(n).padStart(2,'0');};
    var ts = d.getFullYear() + pad(d.getMonth()+1) + pad(d.getDate()) + '-' + pad(d.getHours()) + pad(d.getMinutes());
    a.download = 'stock-events-' + summaryReportFrom.replace(/\//g,'-') + '-to-' + summaryReportTo.replace(/\//g,'-') + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}
JS;
    }
}
