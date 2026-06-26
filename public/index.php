<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require dirname(__DIR__) . '/app/helpers.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = base_path();
$path = $requestPath;

if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $path = substr($requestPath, strlen($basePath)) ?: '/';
}

$path = '/' . trim($path, '/');
$path = $path === '/' ? '/' : rtrim($path, '/');

$routes = [
    '/' => [
        'view' => 'pages/home',
        'title' => 'Dashboard',
        'data' => fn (): array => [
            'dashboardData' => fetch_dashboard_summary($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/about' => [
        'view' => 'pages/about',
        'title' => 'Tentang',
    ],
    '/contact' => [
        'view' => 'pages/contact',
        'title' => 'Kontak',
    ],
    '/sheet' => [
        'view' => 'pages/sheet',
        'title' => 'Google Sheet',
        'data' => fn (): array => [
            'sheet' => fetch_google_sheet_rows(),
        ],
    ],
    '/drive' => [
        'view' => 'pages/drive',
        'title' => 'Google Drive',
        'data' => fn (): array => [
            'drive' => fetch_google_drive_files(),
        ],
    ],
    '/master' => [
        'view' => 'pages/master',
        'title' => 'Data Master',
        'data' => function (): array {
            $tab = $_GET['tab'] ?? 'barang';
            $data = ['tab' => $tab];
            if ($tab === 'customer') {
                $data['masterCustomer'] = fetch_master_customer();
            } elseif ($tab === 'sales') {
                $data['masterSales'] = fetch_master_sales();
            } else {
                $data['tab'] = 'barang';
                $data['masterBarang'] = fetch_master_barang();
            }
            return $data;
        },
    ],
    '/db-maintenance' => [
        'view' => 'pages/database',
        'title' => 'Database',
        'data' => fn (): array => [
            'databaseMaintenance' => fetch_database_maintenance(
                ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? ($_POST['action'] ?? null) : null
            ),
        ],
    ],
    '/operational' => [
        'view' => 'pages/operational',
        'title' => 'Pengeluaran Operasional',
        'data' => fn (): array => [
            'expenses' => fetch_operational_expenses($_GET['month'] ?? '', $_GET['year'] ?? date('Y'), $_GET['status'] ?? '', $_GET['search'] ?? ''),
            'summary' => fetch_operational_summary($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
            'operationalEdit' => fetch_operational_expense_detail($_GET['edit_id'] ?? ''),
        ],
    ],
    '/operational-create' => [
        'view' => 'pages/operational',
        'title' => 'Input Pengeluaran Operasional',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/operational'));
                exit;
            }

            $_SESSION['operational_flash'] = save_operational_expense_form($_POST);
            $redirect = url('/operational') . '?' . http_build_query([
                'month' => $_POST['filter_month'] ?? '',
                'year' => $_POST['filter_year'] ?? date('Y'),
                'status' => $_POST['filter_status'] ?? '',
                'search' => $_POST['filter_search'] ?? '',
            ]);
            header('Location: ' . $redirect);
            exit;
        },
    ],
    '/operational-delete' => [
        'view' => 'pages/operational',
        'title' => 'Hapus Pengeluaran Operasional',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/operational'));
                exit;
            }

            $_SESSION['operational_flash'] = delete_operational_expense((int) ($_POST['operational_id'] ?? 0));
            $redirect = url('/operational') . '?' . http_build_query([
                'month' => $_POST['filter_month'] ?? '',
                'year' => $_POST['filter_year'] ?? date('Y'),
                'status' => $_POST['filter_status'] ?? '',
                'search' => $_POST['filter_search'] ?? '',
            ]);
            header('Location: ' . $redirect);
            exit;
        },
    ],
    '/operational/bonus-sales' => [
        'view' => 'pages/operational-bonus-sales',
        'title' => 'Bonus Sales Internal',
        'data' => fn (): array => [
            'bonusSales' => fetch_internal_sales_bonus($_GET['month'] ?? date('n'), $_GET['year'] ?? date('Y'), [
                'sales' => $_GET['sales'] ?? '',
                'customer_status' => $_GET['customer_status'] ?? '',
                'bonus_status' => $_GET['bonus_status'] ?? '',
            ]),
        ],
    ],
    '/operational/bonus-sales-update' => [
        'view' => 'pages/operational-bonus-sales',
        'title' => 'Update Bonus Sales Internal',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/operational/bonus-sales'));
                exit;
            }

            $month = $_POST['month'] ?? date('n');
            $year = $_POST['year'] ?? date('Y');
            $_SESSION['bonus_sales_flash'] = update_internal_sales_bonus_invoice_status(
                $_POST['kode_invoice'] ?? '',
                $_POST['sales'] ?? '',
                $_POST['status_bonus'] ?? '',
                $_POST['tanggal_bayar_bonus'] ?? '',
                $month,
                $year
            );
            header('Location: ' . url('/operational/bonus-sales') . '?' . http_build_query([
                'month' => $month,
                'year' => $year,
                'sales' => $_POST['filter_sales'] ?? '',
                'customer_status' => $_POST['customer_status'] ?? '',
                'bonus_status' => $_POST['bonus_status'] ?? '',
            ]));
            exit;
        },
    ],
    '/prive' => [
        'view' => 'pages/prive',
        'title' => 'Prive Partner',
        'data' => fn (): array => [
            'priveData' => fetch_partner_prive([
                'month' => $_GET['month'] ?? '',
                'year' => $_GET['year'] ?? date('Y'),
                'partner' => $_GET['partner'] ?? '',
                'status' => $_GET['status'] ?? '',
            ]),
            'priveEdit' => fetch_partner_prive_detail($_GET['edit_id'] ?? ''),
        ],
    ],
    '/prive-save' => [
        'view' => 'pages/prive',
        'title' => 'Simpan Prive Partner',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/prive'));
                exit;
            }

            $_SESSION['prive_flash'] = save_partner_prive_form($_POST);
            $redirect = url('/prive') . '?' . http_build_query([
                'month' => $_POST['filter_month'] ?? '',
                'year' => $_POST['filter_year'] ?? date('Y'),
                'partner' => $_POST['filter_partner'] ?? '',
                'status' => $_POST['filter_status'] ?? '',
            ]);
            header('Location: ' . $redirect);
            exit;
        },
    ],
    '/prive-delete' => [
        'view' => 'pages/prive',
        'title' => 'Hapus Prive Partner',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/prive'));
                exit;
            }

            $_SESSION['prive_flash'] = delete_partner_prive((int) ($_POST['prive_id'] ?? 0));
            $redirect = url('/prive') . '?' . http_build_query([
                'month' => $_POST['filter_month'] ?? '',
                'year' => $_POST['filter_year'] ?? date('Y'),
                'partner' => $_POST['filter_partner'] ?? '',
                'status' => $_POST['filter_status'] ?? '',
            ]);
            header('Location: ' . $redirect);
            exit;
        },
    ],
    '/invoices' => [
        'view' => 'pages/invoices',
        'title' => 'Invoice Mapping',
        'data' => fn (): array => [
            'invoiceMapping' => fetch_invoice_mapping([
                'month' => $_GET['month'] ?? '',
                'year' => $_GET['year'] ?? '',
                'laundry' => $_GET['laundry'] ?? '',
                'sort' => $_GET['sort'] ?? '',
                'direction' => $_GET['direction'] ?? '',
            ]),
        ],
    ],
    '/invoice-payment-log' => [
        'view' => 'pages/invoice-payment-log',
        'title' => 'Log Book Pembayaran Invoice',
        'data' => fn (): array => [
            'paymentLog' => fetch_invoice_payment_log([
                'month' => $_GET['month'] ?? '',
                'year' => $_GET['year'] ?? date('Y'),
                'status' => $_GET['status'] ?? 'unpaid',
                'search' => $_GET['search'] ?? '',
            ]),
        ],
    ],
    '/invoice-payment-update' => [
        'view' => 'pages/invoice-payment-log',
        'title' => 'Update Pembayaran Invoice',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/invoice-payment-log'));
                exit;
            }

            $returnTo = trim((string) ($_POST['return_to'] ?? ''));
            $target = $returnTo !== '' && str_starts_with($returnTo, url('/invoice-payment-log'))
                ? $returnTo
                : url('/invoice-payment-log');

            $result = update_invoice_payment_status(
                $_POST['kode_invoice'] ?? '',
                $_POST['status_pembayaran'] ?? '',
                $_POST['tanggal_pembayaran'] ?? ''
            );

            $_SESSION['invoice_payment_log_flash'] = $result;
            header('Location: ' . $target);
            exit;
        },
    ],
    '/invoice-purchase-log' => [
        'view' => 'pages/invoice-purchase-log',
        'title' => 'Log Book Pembelian Barang',
        'data' => fn (): array => [
            'purchaseLog' => fetch_invoice_purchase_log([
                'month' => $_GET['month'] ?? '',
                'year' => $_GET['year'] ?? date('Y'),
                'status' => $_GET['status'] ?? 'unpaid',
                'search' => $_GET['search'] ?? '',
            ]),
        ],
    ],
    '/invoice-purchase-update' => [
        'view' => 'pages/invoice-purchase-log',
        'title' => 'Update Pembelian Barang',
        'data' => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/invoice-purchase-log'));
                exit;
            }

            $returnTo = trim((string) ($_POST['return_to'] ?? ''));
            $target = $returnTo !== '' && str_starts_with($returnTo, url('/invoice-purchase-log'))
                ? $returnTo
                : url('/invoice-purchase-log');

            $result = update_invoice_purchase_status(
                $_POST['kode_invoice'] ?? '',
                $_POST['status_pembelian_barang'] ?? '',
                $_POST['total_pembelian'] ?? '',
                $_POST['total_utang_pembelian_barang'] ?? '',
                $_POST['tanggal_transfer_pembelian_barang'] ?? ''
            );

            $_SESSION['invoice_purchase_log_flash'] = $result;
            header('Location: ' . $target);
            exit;
        },
    ],
    '/invoice-create' => [
        'view' => 'pages/invoice-create',
        'title' => 'Buat Invoice',
        'data' => function (): array {
            $error = null;
            $googleWarnings = [];
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $isUpdate = !empty($_POST['kode_invoice']);
                $result = save_invoice_form($_POST);
                if ($result['ok']) {
                    $googleSync = queue_invoice_google_sync($result['kode_invoice'], $isUpdate);
                    if (!empty($googleSync['errors'])) {
                        $_SESSION['google_sync_warnings'] = $googleSync['errors'];
                    }
                    header('Location: ' . url('/invoices'));
                    exit;
                }
                $error = $result['message'];
            }
            $invoiceForm = fetch_invoice_form_options($_GET['code'] ?? $_POST['kode_invoice'] ?? '');
            if ($error !== null) {
                $invoiceForm['error'] = $error;
                
                // Preserve POST data in form fields
                $invoiceForm['edit'] = [
                    'mode' => !empty($_POST['kode_invoice']) ? 'update' : 'create',
                    'invoice' => [
                        'kode_invoice' => $_POST['kode_invoice'] ?? '',
                        'nomor_invoice' => $_POST['nomor_invoice'] ?? '',
                        'tanggal_invoice_input' => $_POST['tanggal_invoice'] ?? '',
                        'nomor_surat_jalan' => $_POST['nomor_surat_jalan'] ?? '',
                        'tanggal_surat_jalan_input' => $_POST['tanggal_surat_jalan'] ?? '',
                        'po_number' => $_POST['po_number'] ?? '',
                        'kode_customer' => $_POST['kode_customer'] ?? '',
                        'nama_customer_invoice' => $_POST['nama_customer'] ?? '',
                        'nama_laundry_invoice' => $_POST['nama_customer'] ?? '',
                        'no_telepon' => $_POST['no_telepon'] ?? '',
                        'alamat' => $_POST['alamat'] ?? '',
                        'kode_sales_1' => $_POST['kode_sales_1'] ?? '',
                        'kode_sales_2' => $_POST['kode_sales_2'] ?? '',
                        'status_pembayaran' => $_POST['status_pembayaran'] ?? 'Belum Lunas',
                        'tanggal_pembayaran' => $_POST['tanggal_pembayaran'] ?? '',
                        'jumlah_terbayar_pendapatan' => $_POST['jumlah_terbayar_pendapatan'] ?? '',
                        'discount_persen' => $_POST['discount'] ?? '',
                        'komisi_sales_1_persen' => $_POST['komisi_sales_1_persen'] ?? '',
                        'komisi_sales_2_persen' => $_POST['komisi_sales_2_persen'] ?? '',
                        'komisi_sales_terbayar' => $_POST['komisi_sales_terbayar'] ?? '',
                        'status_pembayaran_komisi_sales' => $_POST['status_pembayaran_sales'] ?? 'Belum TF',
                        'tanggal_transfer_komisi_sales' => $_POST['tanggal_transfer_komisi_sales'] ?? '',
                        'komisi_manager_terbayar' => $_POST['komisi_manager_terbayar'] ?? '',
                        'komisi_manager_utang' => $_POST['komisi_manager_utang'] ?? '',
                        'tanggal_transfer_komisi_manager' => $_POST['tanggal_transfer_manager'] ?? '',
                        'biaya_kirim' => $_POST['biaya_kirim'] ?? '',
                        'biaya_admin_bank' => $_POST['biaya_admin_bank'] ?? '',
                        'total_pembelian_barang' => $_POST['pembelian_barang'] ?? '',
                        'total_utang_pembelian_barang' => $_POST['jumlah_utang_pembelian_barang'] ?? '',
                        'tanggal_transfer_pembelian_barang' => $_POST['tanggal_transfer_pembelian_barang'] ?? '',
                    ],
                    'items' => is_array($_POST['items'] ?? null) ? array_map(static fn($it) => [
                        'kode_barang' => $it['kode_barang'] ?? '',
                        'isi' => $it['isi'] ?? '',
                        'jumlah' => (float)($it['jumlah'] ?? 0),
                        'satuan' => $it['satuan'] ?? '',
                        'harga' => (float)($it['harga'] ?? 0),
                        'total' => (float)($it['total'] ?? 0),
                    ], $_POST['items']) : [],
                ];
            }
            return [
                'invoiceForm' => $invoiceForm,
            ];
        },
    ],
    '/invoice-view' => [
        'view' => 'pages/invoice-view',
        'title' => 'View Invoice',
        'data' => fn (): array => [
            'invoiceDetail' => fetch_invoice_detail($_GET['code'] ?? ''),
        ],
    ],
    '/laporan' => [
        'view' => 'pages/laporan',
        'title' => 'Laporan Utama',
    ],
    '/laporan/penjualan' => [
        'view' => 'pages/laporan-penjualan',
        'title' => 'Laporan Penjualan',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_penjualan($_GET['group'] ?? 'invoice', $_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/profit-loss' => [
        'view' => 'pages/laporan-profit-loss',
        'title' => 'Laporan Profit & Loss',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_profit_loss($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/hutang' => [
        'view' => 'pages/laporan-hutang',
        'title' => 'Laporan Hutang Dagang',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_hutang($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/piutang' => [
        'view' => 'pages/laporan-piutang',
        'title' => 'Laporan Piutang Dagang',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_piutang($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/profit' => [
        'view' => 'pages/laporan-profit',
        'title' => 'Laporan Analisis Profit',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_profit($_GET['group'] ?? 'produk', $_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/komisi' => [
        'view' => 'pages/laporan-komisi',
        'title' => 'Laporan Komisi Sales, Manager & Admin',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_komisi($_GET['month'] ?? '', $_GET['year'] ?? date('Y'), $_GET['status'] ?? ''),
        ],
    ],
    '/laporan/coa' => [
        'view' => 'pages/laporan-coa',
        'title' => 'Chart of Accounts',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_coa(),
        ],
    ],
    '/laporan/jurnal' => [
        'view' => 'pages/laporan-jurnal',
        'title' => 'Jurnal Umum',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_jurnal_umum($_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/buku-besar' => [
        'view' => 'pages/laporan-buku-besar',
        'title' => 'Buku Besar',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_buku_besar($_GET['account'] ?? '', $_GET['month'] ?? '', $_GET['year'] ?? date('Y')),
        ],
    ],
    '/laporan/neraca' => [
        'view' => 'pages/laporan-neraca',
        'title' => 'Neraca',
        'data' => fn (): array => [
            'reportData' => fetch_laporan_neraca($_GET['date'] ?? date('Y-m-d')),
        ],
    ],
    '/invoice-delete' => [
        'view'  => 'pages/invoice-delete-result',
        'title' => 'Hapus Invoice',
        'data'  => function (): array {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                header('Location: ' . url('/invoices'));
                exit;
            }
            $kodeInvoice = trim($_POST['kode_invoice'] ?? '');
            if ($kodeInvoice === '') {
                header('Location: ' . url('/invoices'));
                exit;
            }
            $result = delete_invoice($kodeInvoice);
            if ($result['ok']) {
                header('Location: ' . url('/invoices'));
                exit;
            }
            return ['deleteResult' => $result];
        },
    ],
];

if (! array_key_exists($path, $routes)) {
    http_response_code(404);

    view('pages/404', [
        'title' => 'Halaman Tidak Ditemukan',
    ]);

    exit;
}

$route = $routes[$path];
$routeData = isset($route['data']) ? $route['data']() : [];

view($route['view'], [
    'title' => $route['title'],
    ...$routeData,
]);
