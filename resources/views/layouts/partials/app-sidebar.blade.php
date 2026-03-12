<div id="sidebar-menu">
    <!-- Left Menu Start -->
    <ul class="metismenu list-unstyled" id="side-menu">

        <li class="menu-title" key="t-menu">Menu</li>

        <li>
            <a href="{{ route('dashboard') }}" class="waves-effect">
                <i class="bx bx-home-circle"></i>
                <span key="t-dashboard">Dashboard</span>
            </a>
        </li>



        <li class="menu-title" key="t-asset">Data Barang</li>

        <li>
            <a href="{{ route('asset') }}" class="waves-effect">
                <i class='bx bxs-briefcase'></i>
                <span key="t-karyawan">Data Barang</span>
            </a>
        </li>

        <li class="menu-title" key="t-apps">Transaksi</li>

        <li>
            <a href="{{ route('transaction.create', ['type' => 'out']) }}" class="waves-effect">
                <i class='bx bx-transfer-alt'></i>
                <span key="t-transaction">Konfigurasi Aset</span>
            </a>
        </li>



        <li>
            <a href="{{ route('transaction') }}" class="waves-effect">
                <i class='bx bx-list-ul'></i>
                <span key="t-transaction">Daftar Transaksi</span>
            </a>
        </li>

        <li class="menu-title" key="t-apps">Monitoring</li>

        <li>
            <a href="{{ route('monitor.asset') }}" class="waves-effect">
                <i class='bx bx-list-ul'></i>
                <span key="t-transaction">Per Asset</span>
            </a>
        </li>

        <li>
            <a href="{{ route('monitor.employee') }}" class="waves-effect">
                <i class='bx bx-list-ul'></i>
                <span key="t-transaction">Per Karyawan</span>
            </a>
        </li>

        <li>
            <a href="{{ route('monitor.company') }}" class="waves-effect">
                <i class='bx bx-list-ul'></i>
                <span key="t-transaction">Per Pelanggan</span>
            </a>
        </li>
    </ul>
</div>
