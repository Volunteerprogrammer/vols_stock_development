function PrintPDFTextReport(reportfilename,reporttitle,items,header,footer=[[]]){

/*
reportfilename  "Book Report   .pdf"
reporttitle     "Book Report   "

items:  [   [   ["id"            , "4659"],
                ["245.a,245.b"   , "The lords of the rings, \"power, money and drugs in the modern Olympics\""],
                ["100.a"         , "Simson, Vyv"],
                ["260.b,260.c"   , "Simon and Schuster, 1992" ],
                ["541.a"         , "Presented by a Member, April 2010" ],
                ["90.d,90.e"     , "Basement, BT" ],
                ["520.a"         , ""]], 
            [   ["id"            , "266"],
                ["245.a,245.b"   , "Relief without drugs, \"the self-management of tension, anxiety and pain\""],
                ["100.a"         , "Meares, Ainslie" ],
                ["260.b,260.c"   , "Souvenir Press, 1968" ],
                ["541.a"         , "Presented by a member, December 1977" ],
                ["90.d,90.e"     , "Annexe, B2"],
                ["520.a"         , ""]]]

header: [
            [   ""          ,"id"                                                       ],
            [   "245"       ,"Remainder of title"                           ,"( === )"  ],
            [   "100"       ,"Author"                                       ,""         ],
            [   "260"       ,"Date of publication, distribution, etc."      ,""         ],
            [   "541"       ,"Presentation"                                 ,""         ],
            [   "90"        ,"Shelf Loc"                                    ,""         ],
            [   "520"       ,"Synopsis"                                     ,""         ]
        ]
*/



    window.jsPDF = window.jspdf.jsPDF;
    window.autoTable = window.jspdf.autoTable;
    const leftmargin = 15;
    let pdfdoc = new jsPDF('l');
    let columnstyles = {};
    let styles = {overflow: 'linebreak'};
    let headfootstyles = {halign: 'center', fillColor: 150, fontStyle:'bold' };
    //------------------
    let head = [[]];
    header.forEach((col,idx)=>{
        if (col[3]) { 
            columnstyles[idx] = col[3];
        } 
        head[0].push([col[1]]);
    });
    if (columnstyles === {}) { // no styles provided
        styles.cellWidth = 'auto';
    }
    //------------------
    let body = [];
    items.forEach(item => {
        let row = [];
        item.forEach(col => {
            row.push(col[1]);
        });
        body.push(row);
    });
    //------------------
    let foot = [[]];
    footer.forEach((col,idx) => {
        foot[0].push(col);
    });
    //------------------
    pdfdoc.setFontSize(7);
    pdfdoc.setTextColor(0);
    pdfdoc.autoTable({
        headStyles: headfootstyles,
        footStyles: headfootstyles,
        bodyStyles: {valign: 'top' },
        columnStyles: columnstyles,
        rowPageBreak: 'auto',
        startY: 18,
        head: head,
        body: body,
        foot: foot,
        styles: styles,
        didParseCell: (data) => {
            if ((data.column.index === 0)  && (data.section === 'head' || data.section === 'foot')) {
                data.cell.styles.halign = 'left';
            }
        },
         didDrawPage: function (data) {
            // Header
            pdfdoc.setFontSize(12);
            pdfdoc.text("Woodend Neighbourhood House Food Bank: "+reporttitle,leftmargin,15);//
            // Footer
            // Total page number plugin only available in jspdf v1.0+
            //  if (typeof pdfdoc.putTotalPages === 'function') {
            //      str = str + ' of ' + totalPagesExp; //pdfdoc.putTotalPages(); //totalPagesExp
            //    }
            const pageSize = pdfdoc.internal.pageSize;
            const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
            const pageWidth = pageSize.getWidth();
            pdfdoc.setDrawColor(0, 0, 0);
            pdfdoc.line(leftmargin,pageHeight - 14,leftmargin + pageWidth - 30,pageHeight - 14)
            pdfdoc.setFontSize(8);
            pdfdoc.text('Page ' + pdfdoc.internal.getNumberOfPages(),leftmargin, pageHeight - 10);
            pdfdoc.text(cpc_nowstring(0),leftmargin + pageWidth - 53, pageHeight - 10);
        }
    });
    pdfdoc.save(reportfilename); //
 }