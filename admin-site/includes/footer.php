<!-- Footer -->
<footer class="ml-64 border-t border-gray-100 bg-white mt-8">
    <div class="max-w-7xl mx-auto py-6 px-8">
        <p class="text-center text-sm text-gray-500">Copyright © <?php echo date('Y'); ?> STS. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => modal.style.display = 'none');
    }
    
    window.onclick = function(event) {
        if (event.target.classList && event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    
    function showMessage(message, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = type === 'success' ? 'alert-success' : 'alert-error';
        msgDiv.innerHTML = message;
        msgDiv.style.position = 'fixed';
        msgDiv.style.top = '20px';
        msgDiv.style.right = '20px';
        msgDiv.style.zIndex = '9999';
        document.body.appendChild(msgDiv);
        setTimeout(() => msgDiv.remove(), 3000);
    }

    function downloadCSV(csv, filename) {
        const csvFile = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        const downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();
    }

    function exportTableToCSV(tableSelector, filename, ignoreLastColumn = true) {
        const table = document.querySelector(tableSelector);
        if (!table) {
            alert("Table not found!");
            return;
        }
        const rows = table.querySelectorAll("tr");
        const csv = [];
        
        rows.forEach(row => {
            if (row.style.display === 'none') return;
            
            const cols = row.querySelectorAll("th, td");
            const rowData = [];
            
            const endLength = ignoreLastColumn ? cols.length - 1 : cols.length;
            for (let i = 0; i < endLength; i++) {
                let dataText = cols[i].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                dataText = dataText.replace(/"/g, '""');
                rowData.push('"' + dataText + '"');
            }
            
            csv.push(rowData.join(","));
        });
        
        downloadCSV(csv.join("\n"), filename);
    }

    /**
     * Saves the selected report area as an A4 PDF file.
     * The html2pdf bundle is loaded above so every admin page can use this.
     */
    function exportElementAsPDF(selector, filename, orientation = 'landscape') {
        const element = document.querySelector(selector);
        if (!element) {
            alert('Report content was not found.');
            return;
        }

        if (typeof html2pdf === 'undefined') {
            alert('The PDF generator could not be loaded. Please check your internet connection and try again.');
            return;
        }

        const options = {
            margin: [0.25, 0.25, 0.25, 0.25],
            filename: filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, logging: false, useCORS: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: orientation },
            pagebreak: { mode: ['css', 'legacy'] }
        };

        html2pdf().set(options).from(element).save();
    }
</script>
</body>
</html>
