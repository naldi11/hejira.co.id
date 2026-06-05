import { SidebarProvider, useSidebar } from "../context/SidebarContext";
import { ThemeProvider } from "../context/ThemeContext";
import AppHeader from "./AppHeader";
import Backdrop from "./Backdrop";
import AppSidebar from "./AppSidebar";
import PageBreadcrumb from "../Components/common/PageBreadCrumb";
import React from "react";

import { NavItem } from "./AppSidebar";

const LayoutContent: React.FC<{ children?: React.ReactNode, navItems?: NavItem[], othersItems?: NavItem[], pageTitle?: string }> = ({ children, navItems, othersItems, pageTitle }) => {
  const { isExpanded, isHovered, isMobileOpen } = useSidebar();

  return (
    <div className="min-h-screen xl:flex bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 print:bg-white print:text-black">
      <div className="print:hidden">
        <AppSidebar navItems={navItems} othersItems={othersItems} />
        <Backdrop />
      </div>
      <div
        className={`flex-1 transition-all duration-300 ease-in-out bg-gray-50 dark:bg-gray-950 print:bg-white print:text-black print:ml-0 print:p-0 ${
          isExpanded || isHovered ? "lg:ml-[290px] print:ml-0" : "lg:ml-[90px] print:ml-0"
        } ${isMobileOpen ? "ml-0" : ""}`}
      >
        <div className="print:hidden">
          <AppHeader pageTitle={pageTitle} />
        </div>
        <div className="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6 print:p-0 print:max-w-none">
          {pageTitle && <div className="print:hidden"><PageBreadcrumb pageTitle={pageTitle} /></div>}
          {children}
        </div>
      </div>
    </div>
  );
};


const AppLayout: React.FC<{ children?: React.ReactNode, navItems?: NavItem[], othersItems?: NavItem[], pageTitle?: string }> = ({ children, navItems, othersItems, pageTitle }) => {
  return (
    <ThemeProvider>
      <SidebarProvider>
        <LayoutContent navItems={navItems} othersItems={othersItems} pageTitle={pageTitle}>{children}</LayoutContent>
      </SidebarProvider>
    </ThemeProvider>
  );
};

export default AppLayout;

