import { useState } from 'react';
import Sidebar from './Sidebar';
import TopBar from './TopBar';

export default function Layout({ children }) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar onCollapseChange={setSidebarCollapsed} />
      <div className={`flex-1 transition-all duration-300 ${sidebarCollapsed ? 'ml-16' : 'ml-64'}`}>
        <TopBar sidebarCollapsed={sidebarCollapsed} />
        <main className="pt-16">
          <div className="p-6">
            <div className="mt-6">{children}</div>
          </div>
        </main>
      </div>
    </div>
  );
}
