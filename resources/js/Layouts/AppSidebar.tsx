import { useCallback, useEffect, useMemo, useState } from "react";
import { Link, usePage } from "@inertiajs/react";

import Icon from "@/Components/Icon";
import { useSidebar } from "../context/SidebarContext";

export type NavItem = {
  name: string;
  icon?: React.ReactNode;
  path?: string;
  subItems?: { name: string; path: string; pro?: boolean; new?: boolean }[];
};

interface AppSidebarProps {
  navItems?: NavItem[];
  othersItems?: NavItem[];
}

const ENTITY_BRAND: Record<string, { full: string; short: string }> = {
  izzi: { full: "Izzi Kebab", short: "IZ" },
  jihans:  { full: "Jihan's Food",      short: "JF" },
  hendhys: { full: "Hendhy's Brownies", short: "HB" },
  gudang:  { full: "HEJIRA",            short: "H"  },
};

const AppSidebar: React.FC<AppSidebarProps> = ({ navItems = [], othersItems = [] }) => {
  const { isExpanded, isMobileOpen, isHovered, setIsHovered } = useSidebar();
  const { url, props } = usePage() as any;
  const entity: string = props?.auth?.user?.entity ?? 'gudang';
  const brand = ENTITY_BRAND[entity] ?? ENTITY_BRAND['gudang'];

  const [openSubmenu, setOpenSubmenu] = useState<{
    type: "main" | "others";
    index: number;
  } | null>(null);
  // Inertia supplies relative URLs while Ziggy may supply absolute URLs.
  // Prefer the most specific route, including its declared tab parameters.
  const activePath = useMemo(() => {
    const current = new URL(url, "http://navigation.local");
    const paths = [...navItems, ...othersItems].flatMap(nav =>
      nav.subItems ? nav.subItems.map(item => item.path) : nav.path ? [nav.path] : []
    );
    return paths.map(path => {
      const target = new URL(path, "http://navigation.local");
      const base = target.pathname.replace(/\/$/, "") || "/";
      const matchesPath = current.pathname === target.pathname ||
        (base !== "/" && current.pathname.startsWith(`${base}/`));
      const matchesQuery = Array.from(target.searchParams).every(([key, value]) => current.searchParams.get(key) === value);
      return { path, matches: matchesPath && matchesQuery, score: base.length * 1000 + target.search.length };
    }).filter(item => item.matches).sort((a, b) => b.score - a.score)[0]?.path;
  }, [url, navItems, othersItems]);
  const isActive = useCallback((path: string) => path === activePath, [activePath]);

  useEffect(() => {
    let submenuMatched = false;
    ["main", "others"].forEach((menuType) => {
      const items = menuType === "main" ? navItems : othersItems;
      items.forEach((nav, index) => {
        if (nav.subItems) {
          nav.subItems.forEach((subItem) => {
            if (isActive(subItem.path)) {
              setOpenSubmenu({
                type: menuType as "main" | "others",
                index,
              });
              submenuMatched = true;
            }
          });
        }
      });
    });

    if (!submenuMatched) {
      setOpenSubmenu(null);
    }
  }, [url, isActive]);

  const handleSubmenuToggle = (index: number, menuType: "main" | "others") => {
    setOpenSubmenu((prevOpenSubmenu) => {
      if (
        prevOpenSubmenu &&
        prevOpenSubmenu.type === menuType &&
        prevOpenSubmenu.index === index
      ) {
        return null;
      }
      return { type: menuType, index };
    });
  };

  const renderMenuItems = (items: NavItem[], menuType: "main" | "others") => (
    <ul className="flex flex-col gap-2">
      {items.map((nav, index) => (
        <li key={nav.name}>
          {nav.subItems ? (
            <button
              aria-expanded={openSubmenu?.type === menuType && openSubmenu?.index === index}
              aria-controls={`submenu-${menuType}-${index}`}
              onClick={() => handleSubmenuToggle(index, menuType)}
              title={nav.name}
              className={`menu-item group min-h-11 text-left ${
                openSubmenu?.type === menuType && openSubmenu?.index === index
                  ? "menu-item-active"
                  : "menu-item-inactive"
              } cursor-pointer ${
                !isExpanded && !isHovered
                  ? "lg:justify-center"
                  : "lg:justify-start"
              }`}
            >
              <span
                className={`menu-item-icon-size shrink-0  ${
                  openSubmenu?.type === menuType && openSubmenu?.index === index
                    ? "menu-item-icon-active"
                    : "menu-item-icon-inactive"
                }`}
              >
                {nav.icon}
              </span>
              {(isExpanded || isHovered || isMobileOpen) && (
                <span className="menu-item-text min-w-0 flex-1 whitespace-normal break-words text-left leading-5">{nav.name}</span>
              )}
              {(isExpanded || isHovered || isMobileOpen) && (
                <Icon name="expand_more"
                  className={`text-[20px] ml-auto shrink-0 transition-transform duration-200 ${
                    openSubmenu?.type === menuType &&
                    openSubmenu?.index === index
                      ? "rotate-180 text-brand-500"
                      : ""
                  }`}
                />
              )}
            </button>
          ) : (
            nav.path && (
              <Link
                href={nav.path}
                aria-current={isActive(nav.path) ? "page" : undefined}
                title={nav.name}
                className={`menu-item group min-h-11 text-left ${
                  isActive(nav.path) ? "menu-item-active" : "menu-item-inactive"
                }`}
              >
                <span
                  className={`menu-item-icon-size shrink-0 ${
                    isActive(nav.path)
                      ? "menu-item-icon-active"
                      : "menu-item-icon-inactive"
                  }`}
                >
                  {nav.icon}
                </span>
                {(isExpanded || isHovered || isMobileOpen) && (
                  <span className="menu-item-text min-w-0 flex-1 whitespace-normal break-words text-left leading-5">{nav.name}</span>
                )}
              </Link>
            )
          )}
          {nav.subItems && (isExpanded || isHovered || isMobileOpen) && (
            <div
              id={`submenu-${menuType}-${index}`}
              aria-hidden={!(openSubmenu?.type === menuType && openSubmenu?.index === index)}
              className="grid transition-[grid-template-rows] duration-300 motion-reduce:transition-none"
              style={{
                gridTemplateRows:
                  openSubmenu?.type === menuType && openSubmenu?.index === index
                    ? "1fr"
                    : "0fr",
              }}
            >
              <div className="min-h-0 overflow-hidden">
              <ul className="mt-1 space-y-1 ml-4 border-l border-gray-200 pl-3 dark:border-gray-700">
                {nav.subItems.map((subItem) => (
                  <li key={subItem.name}>
                    <Link
                      href={subItem.path}
                      tabIndex={openSubmenu?.type === menuType && openSubmenu?.index === index ? undefined : -1}
                      aria-current={isActive(subItem.path) ? "page" : undefined}
                      className={`menu-dropdown-item ${
                        isActive(subItem.path)
                          ? "menu-dropdown-item-active"
                          : "menu-dropdown-item-inactive"
                      }`}
                    >
                      <span className="min-w-0 flex-1 whitespace-normal break-words text-left leading-5">{subItem.name}</span>
                      {(subItem.new || subItem.pro) && <span className="flex shrink-0 items-center gap-1 ml-auto">
                        {subItem.new && (
                          <span
                            className={`ml-auto ${
                              isActive(subItem.path)
                                ? "menu-dropdown-badge-active"
                                : "menu-dropdown-badge-inactive"
                            } menu-dropdown-badge`}
                          >
                            new
                          </span>
                        )}
                        {subItem.pro && (
                          <span
                            className={`ml-auto ${
                              isActive(subItem.path)
                                ? "menu-dropdown-badge-active"
                                : "menu-dropdown-badge-inactive"
                            } menu-dropdown-badge`}
                          >
                            pro
                          </span>
                        )}
                      </span>}
                    </Link>
                  </li>
                ))}
              </ul>
              </div>
            </div>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <aside
      className={`fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-[calc(100dvh-4rem)] lg:h-dvh transition-all duration-300 ease-in-out z-50 border-r border-gray-200
        w-[290px] ${
          isExpanded || isHovered
            ? "lg:w-[290px]"
            : "lg:w-[90px]"
        }
        ${isMobileOpen ? "translate-x-0" : "-translate-x-full"}
        lg:translate-x-0`}
      onMouseEnter={() => !isExpanded && setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div
        className={`shrink-0 py-6 flex ${
          !isExpanded && !isHovered ? "lg:justify-center" : "justify-start"
        }`}
      >
        <Link href="/" className="min-w-0 flex items-center gap-2">
          {isExpanded || isHovered || isMobileOpen ? (
            <div className="min-w-0 flex flex-col">
              <span className="text-xl font-extrabold tracking-wide bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent dark:from-brand-400 dark:to-brand-200 leading-tight">
                {brand.full}
              </span>
              {props?.auth?.user?.branch?.name && (
                <span className="text-[11px] font-semibold text-slate-500 dark:text-gray-400 mt-1 uppercase tracking-wider block">
                  📍 {props.auth.user.branch.name}
                </span>
              )}
            </div>
          ) : (
            <span className="text-2xl font-extrabold tracking-wider bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent dark:from-brand-400 dark:to-brand-200">
              {brand.short}
            </span>
          )}
        </Link>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-6">
        <nav className="mb-6">
          <div className="flex flex-col gap-2">
            <div>
              <h2
                className={`mb-3 text-xs uppercase flex leading-[20px] text-gray-400 ${
                  !isExpanded && !isHovered
                    ? "lg:justify-center"
                    : "justify-start"
                }`}
              >
                {isExpanded || isHovered || isMobileOpen ? (
                  "Menu"
                ) : (
                  <Icon name="more_horiz" className="text-[24px]" />
                )}
              </h2>
              {renderMenuItems(navItems, "main")}
            </div>
              {othersItems.length > 0 && (
                <div className="">
                  <h2
                    className={`mb-3 text-xs uppercase flex leading-[20px] text-gray-400 ${
                      !isExpanded && !isHovered
                        ? "lg:justify-center"
                        : "justify-start"
                    }`}
                  >
                    {isExpanded || isHovered || isMobileOpen ? (
                      "Lainnya"
                    ) : (
                      <Icon name="more_horiz" className="text-[20px]" />
                    )}
                  </h2>
                  {renderMenuItems(othersItems, "others")}
                </div>
              )}
          </div>
        </nav>
      </div>
    </aside>
  );
};

export default AppSidebar;
