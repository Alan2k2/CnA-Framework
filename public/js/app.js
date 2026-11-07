function copyQuickCmd() {
            const text = document.getElementById('quickCmd').innerText;
            navigator.clipboard.writeText(text);
            alert('Copied to clipboard: ' + text);
        }

        function copyText(text) {
            navigator.clipboard.writeText(text);
            alert('Copied: ' + text);
        }

        function filterCommands(cat, btn) {
            document.querySelectorAll('.cli-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const rows = document.querySelectorAll('#cliTableBody tr');
            rows.forEach(row => {
                if (cat === 'all' || row.getAttribute('data-cat') === cat) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        async function executeApiCall() {
            const select = document.getElementById('endpointSelect');
            const terminal = document.getElementById('terminalOutput');
            const endpoint = select.value;

            terminal.innerText = `// Sending GET ${endpoint} ...\n`;

            try {
                const start = performance.now();
                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });
                const elapsed = (performance.now() - start).toFixed(1);
                const data = await res.json();

                terminal.innerText = `// HTTP Status: ${res.status} ${res.statusText} (${elapsed}ms)\n// Headers: Content-Type: ${res.headers.get('content-type')}\n\n` + JSON.stringify(data, null, 2);
            } catch (err) {
                terminal.innerText = `// Error: ${err.message}`;
            }
        }