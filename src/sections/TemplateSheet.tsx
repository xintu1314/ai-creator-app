import { useState } from 'react';
import { X, Image, Video } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import type { Template } from '@/types';

interface TemplateSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  templates: Template[];
  type: 'image' | 'video';
  onUseTemplate: (template: Template) => void;
}

export default function TemplateSheet({
  open,
  onOpenChange,
  templates,
  type,
  onUseTemplate,
}: TemplateSheetProps) {
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
  const [selectedCategory, setSelectedCategory] = useState<string>('全部');

  const categories = ['全部', '室内', '景观', '建筑', '电商', '人物', '动物', '自然'];

  const filteredTemplates = selectedCategory === '全部' 
    ? templates 
    : templates.filter(t => t.category === selectedCategory);

  // 当类别切换时，重置选中的模板
  const handleCategoryChange = (category: string) => {
    setSelectedCategory(category);
    setSelectedTemplate(null);
  };

  const handleTemplateClick = (template: Template) => {
    setSelectedTemplate(template);
  };

  const handleMakeSimilar = () => {
    if (selectedTemplate) {
      onUseTemplate(selectedTemplate);
      onOpenChange(false);
      setSelectedTemplate(null);
    }
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent 
        side="bottom" 
        showCloseButton={false}
        className="h-[60vh] max-h-[60vh] p-0 flex flex-col border-t rounded-t-2xl data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom"
      >
        <SheetHeader className="px-6 py-4 border-b border-[#E5E5E5] flex-shrink-0">
          <div className="flex items-center justify-between mb-4">
            <SheetTitle className="text-lg font-medium text-[#1A1A1A]">
              {type === 'image' ? '图片灵感' : '视频灵感'}
            </SheetTitle>
            <button
              onClick={() => onOpenChange(false)}
              className="p-2 hover:bg-[#F5F5F5] rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-[#666666]" />
            </button>
          </div>
          
          {/* Category Filter */}
          <div className="flex items-center gap-2 overflow-x-auto pb-2">
            {categories.map((category) => (
              <button
                key={category}
                onClick={() => handleCategoryChange(category)}
                className={cn(
                  "px-4 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap transition-colors",
                  selectedCategory === category
                    ? "bg-[#3B82F6] text-white"
                    : "bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
                )}
              >
                {category}
              </button>
            ))}
          </div>
        </SheetHeader>

        <div className="flex-1 overflow-hidden flex flex-col min-h-0">
          {/* Templates Grid */}
          <div className="flex-1 overflow-y-auto p-4 min-h-0">
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
              {filteredTemplates.map((template) => (
                <div
                  key={template.id}
                  className={cn(
                    "group cursor-pointer",
                    selectedTemplate?.id === template.id && "ring-2 ring-[#3B82F6] rounded-xl p-1"
                  )}
                  onClick={() => handleTemplateClick(template)}
                >
                  <div className="relative rounded-xl overflow-hidden shadow-md group-hover:shadow-lg transition-all duration-300 aspect-[3/4]">
                    {/* Image */}
                    <img
                      src={template.image}
                      alt={template.title}
                      className="w-full h-full object-cover"
                    />
                    
                    {/* Model Tag */}
                    <div className="absolute top-1.5 left-1.5">
                      <span className="px-1.5 py-0.5 text-[9px] bg-black/60 text-white rounded backdrop-blur-sm">
                        {template.model}
                      </span>
                    </div>

                    {/* Title Overlay */}
                    <div className="absolute bottom-0 left-0 right-0 p-1.5 bg-gradient-to-t from-black/80 to-transparent">
                      <p className="text-[10px] text-white line-clamp-2 leading-tight">
                        {template.title}
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Bottom Action Bar */}
          {selectedTemplate && (
            <div className="border-t border-[#E5E5E5] bg-white p-4 flex-shrink-0">
              <div className="max-w-[1200px] mx-auto flex items-center gap-4">
                {/* Preview Image */}
                <div className="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0">
                  <img
                    src={selectedTemplate.image}
                    alt={selectedTemplate.title}
                    className="w-full h-full object-cover"
                  />
                </div>

                {/* Input Area */}
                <div className="flex-1 flex items-center gap-3">
                  <div className="w-[80px] h-[80px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all">
                    <span className="text-2xl text-[#999999]">+</span>
                  </div>
                  <input
                    type="text"
                    placeholder="试试描述一段简短的故事情节，最关键的是主体、环境、时间、风格"
                    className="flex-1 h-[80px] px-4 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6] transition-colors"
                  />
                </div>

                {/* Action Buttons */}
                <div className="flex items-center gap-3">
                  <button
                    onClick={handleMakeSimilar}
                    className="h-[80px] px-6 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors whitespace-nowrap"
                  >
                    做同款
                  </button>
                  <button
                    onClick={() => {
                      onUseTemplate(selectedTemplate);
                      onOpenChange(false);
                      setSelectedTemplate(null);
                    }}
                    className="h-[80px] px-6 text-sm font-medium bg-white border border-[#E5E5E5] hover:bg-[#F5F5F5] text-[#1A1A1A] rounded-lg transition-colors whitespace-nowrap"
                  >
                    生成
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </SheetContent>
    </Sheet>
  );
}
