import { useState } from 'react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Template } from '@/types';

interface TemplateCardsProps {
  templates: Template[];
  title: string;
  type: 'image' | 'video';
  onUseTemplate: (template: Template) => void;
  onViewMore?: () => void;
}

export default function TemplateCards({ templates, title, type, onUseTemplate, onViewMore }: TemplateCardsProps) {
  const [hoveredId, setHoveredId] = useState<string | null>(null);

  return (
    <div className="mt-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-base font-medium text-[#1A1A1A]">{title}</h3>
        <button 
          onClick={onViewMore}
          className="flex items-center text-sm text-[#666666] hover:text-[#3B82F6] transition-colors"
        >
          查看更多
          <ChevronRight className="w-4 h-4 ml-0.5" />
        </button>
      </div>

      {/* Cards Grid - Horizontal Scroll */}
      <div className="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
        {templates.map((template) => (
          <div
            key={template.id}
            className="flex-shrink-0 w-[160px] group"
            onMouseEnter={() => setHoveredId(template.id)}
            onMouseLeave={() => setHoveredId(null)}
          >
            <div 
              className={cn(
                "relative w-full h-[200px] rounded-xl overflow-hidden cursor-pointer",
                "transition-all duration-300",
                hoveredId === template.id ? "shadow-lg -translate-y-1" : "shadow-md"
              )}
            >
              {/* Image */}
              <img
                src={template.image}
                alt={template.title}
                className="w-full h-full object-cover"
              />
              
              {/* Model Tag */}
              <div className="absolute top-2 left-2">
                <span className="px-2 py-0.5 text-[10px] bg-black/60 text-white rounded backdrop-blur-sm">
                  {template.model}
                </span>
              </div>

              {/* Title Overlay */}
              <div className="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
                <p className="text-xs text-white line-clamp-2">{template.title}</p>
              </div>

              {/* Hover Overlay with Action Button */}
              <div 
                className={cn(
                  "absolute inset-0 bg-black/40 flex items-end justify-center p-3",
                  "transition-all duration-250",
                  hoveredId === template.id ? "opacity-100" : "opacity-0"
                )}
              >
                <button
                  onClick={() => onUseTemplate(template)}
                  className={cn(
                    "w-full py-2 text-sm font-medium text-[#1A1A1A] bg-white rounded-lg",
                    "transform transition-all duration-250",
                    hoveredId === template.id ? "translate-y-0" : "translate-y-full"
                  )}
                >
                  做同款
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
